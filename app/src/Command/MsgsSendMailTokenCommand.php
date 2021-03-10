<?php

namespace App\Command;

use Symfony\Component\Process\Process;
use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Service\LogService;
use Swift_Mailer;
use Swift_Message;
use Swift_RfcComplianceException;
use Swift_SmtpTransport;
use Swift_TransportException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;

//use App\Service\CryptEncrypt;

class MsgsSendMailTokenCommand extends ContainerAwareCommand {

  protected static $defaultName = 'agentj:msgs-send-mail-token';
  private $translator;
  private $messageStatusError;
  private $messageStatusAuthorized;
  private $em;

  protected function configure() {
    $this
            ->setDescription('Send email with url token to validate email with captcha')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {


    $store = new SemaphoreStore();
    $factory = new Factory($store);
    $lock = $factory->createLock('msgs-send-mail-token', 1800);

    if ($lock->acquire()) {
      $this->translator = $this->getContainer()->get('translator');

      $this->em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager();
      $this->messageStatusError = $this->em->getRepository(MessageStatus::class)->find(4); //status error
      $this->messageStatusAuthorized = $this->em->getRepository(MessageStatus::class)->find(2); //status authorized
      $this->em->getRepository(Msgs::class)->updateErrorStatus();
      $msgs = $this->em->getRepository(Msgs::class)->searchMsgsToSendAuthToken();

      foreach ($msgs as $msg) {

        $email_clean = "";
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $msg['from_addr'], $email_clean);
        if (isset($email_clean[0][0]) && filter_var($email_clean[0][0], FILTER_VALIDATE_EMAIL)) {

          $msg['from_addr'] = filter_var($email_clean[0][0], FILTER_VALIDATE_EMAIL);

          if (!$msg['from_addr']) {
            continue;
          }

          $msgObj = $this->em->getRepository(Msgs::class)->findOneBy(['mailId' => $msg['mail_id']]);

          /* @var $msgrcpt Msgrcpt */
          $msgrcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy(['mailId' => $msg['mail_id'], 'rid' => $msg['rid']]);

          if (is_null($msgrcpt)) {
            continue;
          }


          $destEmail = stream_get_contents($msgrcpt->getRid()->getEmail(), -1, 0);
          /* @var $user User */
          $user = $this->em->getRepository(User::class)->findOneBy(['email' => $destEmail]);
          if ($user) {
            $fromName = $user->getFullName() ? $user->getFullName() : '';
            if ($user->getDomain()->getMailAuthenticationSender() && filter_var($user->getDomain()->getMailAuthenticationSender(), FILTER_VALIDATE_EMAIL)) {
              $mailFrom = $user->getDomain()->getMailAuthenticationSender();
            } else {
              $mailFrom = $this->getApplication()->getKernel()->getContainer()->getParameter('app.domain_mail_authentification_sender');
            }

            $destDomain = $user->getDomain();
          } else {
            continue;
          }

          if ($user->getBypassHumanAuth()) {
            $this->releaseMessage($msgObj, $msgrcpt, $user);
          } else {
            // Bypass if an authetification has been sent the last day for this sender
            $AuhtentificationAllreadySent = $this->em->getRepository(Msgs::class)->checkLastRequestSent($destEmail, $msg['from_addr']);
            if (count($AuhtentificationAllreadySent) > 0) {
              $today = new \DateTime();
              $dateLastSendAuthentification = new \DateTime($AuhtentificationAllreadySent[0]['time_iso']);
              $interval = $today->diff($dateLastSendAuthentification)->format('%a');
              if ($interval <= 0) {
                continue;
              }
            }

            $mailBody = $this->createAuthMessageContent($destDomain, $msg, $destEmail);
            $message = $this->createAuthMessage($msgObj, $mailFrom, $fromName, $mailBody);
            if ($message) {
              if ($this->sendAuthMessage($msgObj, $message, $msgrcpt)) {
                $logService = new LogService($this->em);
                $logService->addLog('Authentification request sent', $msg['mail_id'], $mailBody['html_body']);
                $mailTo = stream_get_contents($msgObj->getSid()->getEmail(), -1, 0);
                $subject = $this->getSubject($msgObj);
                $output->writeln(date('Y-m-d H:i:s') . "\t" . $mailFrom . "<" . $fromName . ">" . "\t" . $msg['mail_id'] . "\t" . $mailTo . "\t" . $subject);
              }
            } else {
              $msgObj = $this->em->getRepository(Msgs::class)->findOneBy(['mailId' => $msg['mail_id']]);
              if ($msgObj) {
                $msgObj->setMessageError(sprintf('Unable to create Swift_Message ', $msg['from_addr']));
                $msgObj->setStatus($this->messageStatusError);
                $this->em->persist($msgObj);
                $this->em->flush();
              }
            }
          }
        } else {
          $msgObj = $this->em->getRepository(Msgs::class)->findOneBy(['mailId' => $msg['mail_id']]);
          if ($msgObj) {
            $msgObj->setMessageError(sprintf('Email %s is not valid', $msg['from_addr']));
            $msgObj->setStatus($this->messageStatusError);
            $this->em->persist($msgObj);
            $this->em->flush();
          }
        }
      }
      //update the msgs error
      $this->em->getRepository(Msgs::class)->updateErrorStatus();

      $lock->release();
    }
  }

  /**
   * 
   * @param Domain $destDomain
   * @param type $msg
   * @param type $destEmail
   * @return type
   */
  private function createAuthMessageContent(Domain $destDomain, $msg, $destEmail) {
    $domain = $this->getApplication()->getKernel()->getContainer()->getParameter('domain');
    $scheme = $this->getApplication()->getKernel()->getContainer()->getParameter('scheme');
    $cryptEncrypt = $this->getApplication()->getKernel()->getContainer()->get('App.crypt_encrypt');

    /** Body Mail Body Settings * */
    $token = $cryptEncrypt->encryptUrl($msg['mail_id'] . '%%%' . $msg['secret_id'] . '%%%' . $msg['partition_tag'] . '%%%' . $destDomain->getId() . '%%%' . $msg['rid']);
    $url = $scheme . "://" . $domain . "/check/" . $token;

    if ($destDomain && !empty($destDomain->getMailmessage())) {
      $body = $destDomain->getMailmessage();
    } else {
      $body = $this->translator->trans('Message.Captcha.defaultMailContent');
    }
    $body = str_replace('[URL_CAPTCHA]', $url, $body);
    $body = str_replace('[EMAIL_DEST]', $destEmail, $body);
    $bodyTextPlain = preg_replace("/\r|\n|\t/", "", $body);
    $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $bodyTextPlain);
    $bodyTextPlain = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
      return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
    }, $bodyTextPlain);
    $bodyTextPlain = html_entity_decode($bodyTextPlain);
    $bodyTextPlain = strip_tags($bodyTextPlain);
    $bodyTextPlain .= "\n" . $url;
    return [
        'html_body' => $body,
        'plain_body' => $bodyTextPlain,
    ];
  }

  /**
   * Set the subject of the mail send captcha from original subject
   * @param Msgs $msgObj
   * @return type
   */
  private function getSubject(Msgs $msgObj) {
    if ($msgObj->getSubject() && strlen($msgObj->getSubject()) > 0) {
      $subject = 'Re : ' . $msgObj->getSubject();
    } else {
      $subject = $this->translator->trans('Message.Captcha.defaultMailSubject');
    }
    return $subject;
  }

  /**
   * create message instance 
   * @param Msgs $msgObj
   * @param String $mailFrom
   * @param type $fromName
   * @param String $body
   * @return Swift_Message
   */
  private function createAuthMessage(Msgs $msgObj, String $mailFrom, String $fromName, Array $body) {


    $mailTo = stream_get_contents($msgObj->getSid()->getEmail(), -1, 0);
    try {
      // 
      if ($msgObj->getSubject() && strlen($msgObj->getSubject()) > 0) {
        $subject = 'Re : ' . $msgObj->getSubject();
      } else {
        $subject = $this->translator->trans('Message.Captcha.defaultMailSubject');
      }
      $subject = $this->getSubject($msgObj);

      $message = (new Swift_Message($subject))
              ->setContentType("text/html")
              ->setTo($mailTo)
              ->setFrom($mailFrom, $fromName)
              ->setBody($body['html_body'])
              ->addPart($body['plain_body'], 'text/plain');
    } catch (Swift_RfcComplianceException $e) {
      //catch error and save this in msgs + change status to error
      $messageError = $e->getMessage();
      $msgObj->setMessageError($messageError);
      $msgObj->setStatus($this->messageStatusError);
      $this->em->persist($msgObj);
      $this->em->flush();
      $message = null;
    }
    return $message;
  }

  /**
   * Send an authentification request message
   * @param Msgs $msgObj
   * @param type $message
   * @param type $msgrcpt
   * @return boolean
   */
  private function sendAuthMessage(Msgs $msgObj, Swift_Message $message, $msgrcpt) {

    $mailTo = stream_get_contents($msgObj->getSid()->getEmail(), -1, 0);
    $failedRecipients = [];
    /** Sne dth emessage * */
    try {
      $transport_server = $this->getApplication()->getKernel()->getContainer()->getParameter('app.smtp-transport');
      $transport = new Swift_SmtpTransport($transport_server);
      $domainEmailTo = $this->em->getRepository(Domain::class)->findOneBy(['domain' => strtolower(substr($mailTo, strpos($mailTo, '@') + 1))]);
      if ($domainEmailTo) {
        $transport = new Swift_SmtpTransport($domainEmailTo->getSrvSmtp(), 25);
      }

      $mailer = new Swift_Mailer($transport);
      $mailer->send($message, $failedRecipients);
      $msgObj->setSendCaptcha(time());
      $msgrcpt->setSendCaptcha(time());
      $this->em->persist($msgObj);
      $this->em->persist($msgrcpt);
      $this->em->flush();
      return true;
    } catch (Swift_TransportException $e) {
      //catch error and save this in msgs + change status to error
      $messageError = $e->getMessage();
      $msgObj->setMessageError($messageError);
      $msgObj->setStatus($this->messageStatusError);
      $this->em->persist($msgObj);
      $this->em->flush();
      return false;
    }
  }

  private function releaseMessage(Msgs $msgObj, Msgrcpt $msgRcpt, User $user) {
    $cmd = (string) $this->getApplication()->getKernel()->getContainer()->getParameter('app.amavisd-release');
    $process = new Process([
        $cmd,
        stream_get_contents($msgObj->getQuarLoc(), -1, 0),
        stream_get_contents($msgObj->getSecretId(), -1, 0),
        $user->getEmailFromRessource()
    ]);
   // $process->getCommandLine();
    $process->run(
            function ($type, $buffer) use($msgRcpt) {
      $msgRcpt->setAmavisOutput($buffer);
    }
    );
    $msgRcpt->setStatus($this->messageStatusAuthorized);
    $this->em->persist($msgRcpt);
    $this->em->flush();
  }

}
