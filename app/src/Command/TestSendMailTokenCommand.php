<?php

namespace App\Command;

use App\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_TransportException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;

//use App\Service\CryptEncrypt;

class TestSendMailTokenCommand extends ContainerAwareCommand {

  protected static $defaultName = 'agentj:test-send-mail-token';

  protected function configure() {
    $this
            ->setDescription('Test sender mail')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {


    $store = new SemaphoreStore();
    $factory = new Factory($store);
    $lock = $factory->createLock('test-send-email');
    $io = new SymfonyStyle($input, $output);
    $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager();
    $container = $this->getApplication()->getKernel()->getContainer();
    if ($lock->acquire()) {
//      sleep(120);
      $userMail = $io->ask('From ');
      $user = $user = $em->getRepository(User::class)->findOneBy(['email' => $userMail]);
      if (!$user) {
        throw new \Exception('User does not exist in database');
      }
      $destMail = $io->ask('To ');

      $userDomain = $user->getDomain();
      if ($userDomain && !empty($userDomain->getMailmessage())) {
        $body = $userDomain->getMailmessage();
      } else {
        $body = $translator->trans('Message.Captcha.defaultMailContent');
      }
      $cryptEncrypt = $container->get('App.crypt_encrypt');
      $token = $cryptEncrypt->encryptUrl('0-4ErKhAnNBa' . '%%%' . 'ThgDiHj5bYIB' . '%%%' . '0' . '%%%' . $userDomain->getId() . '%%%' . $user->getId());
//      dd($body);


      $domain = $container->getParameter('domain');
      $scheme = $container->getParameter('scheme');
      $url = $scheme . "://" . $domain . "/check/" . $token;

      $body = str_replace('[URL_CAPTCHA]', $url, $body);
      $body = str_replace('[EMAIL_DEST]', $userMail, $body);

      $bodyTextPlain = preg_replace("/\r|\n|\t/", "", $body);
      $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $bodyTextPlain);

      $bodyTextPlain = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
        return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
      }, $bodyTextPlain);
      $bodyTextPlain = html_entity_decode($bodyTextPlain);
      $bodyTextPlain = strip_tags($bodyTextPlain);
      $bodyTextPlain .= "\n" . $url;
      $subject = 'Re : MEssage test';

      $transport = new Swift_SmtpTransport($this->getApplication()->getKernel()->getContainer()->getParameter('app.smtp-transport'));
      $mailer = new Swift_Mailer($transport);
      $mailFrom = $container->getParameter('app.domain_mail_authentification_sender');
      
      $message = (new Swift_Message($subject))
              ->setContentType("text/html")
              ->setTo($destMail)
              ->setFrom($mailFrom, $user->getFullName())
              ->setBody($body)
              ->addPart($bodyTextPlain, 'text/plain');

      try {
//        dump($mailer);
        $mailer->send($message, $failedRecipients);
      } catch (Swift_TransportException $e) {

        //catch error and save this in msgs + change status to error
        $messageError = $e->getMessage();
        $io->note("Error " . $messageError);
//        dump($messageError);
      }
      $lock->release();
    } else {
      $io->caution("ressource locked");
    }
  }

}
