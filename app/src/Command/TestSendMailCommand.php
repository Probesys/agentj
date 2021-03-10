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

class TestSendMailCommand extends ContainerAwareCommand {

  protected static $defaultName = 'agentj:test-send-mail';

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
      $destMail = $io->ask('To ');
      $userMail = $io->ask('From ');
      $useSameReturnPath = $io->ask('Use Same return_path ? (y/n)');
      if ($useSameReturnPath=='y' || empty($useSameReturnPath)){
        $returnPath = $userMail;
      }
      else{
        $returnPath = uniqid() . "-" . $userMail;
      }
     

      $body = 'Message.Captcha.defaultMailContent';


      $domain = $container->getParameter('domain');
      $scheme = $container->getParameter('scheme');


      $bodyTextPlain = preg_replace("/\r|\n|\t/", "", $body);
      $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $bodyTextPlain);

      $bodyTextPlain = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
        return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
      }, $bodyTextPlain);
      $bodyTextPlain = html_entity_decode($bodyTextPlain);
      $bodyTextPlain = strip_tags($bodyTextPlain);
      $bodyTextPlain .= "\n" . $bodyTextPlain;
      $subject = 'Re : MEssage test';

      //$transport = new Swift_SmtpTransport($this->getApplication()->getKernel()->getContainer()->getParameter('app.smtp-transport'));
      $transport = new Swift_SmtpTransport("2.56.156.246");
      $mailer = new Swift_Mailer($transport);
      $mailFrom = $container->getParameter('app.domain_mail_authentification_sender');
      
      $message = (new Swift_Message($subject))
              ->setContentType("text/html")
              ->setTo($destMail)
              ->setFrom($userMail, "tester")
              ->setReturnPath($returnPath)
              ->setBody($body)
              ->addPart($bodyTextPlain, 'text/plain');

      $isMlist = $io->ask('Add List-id in header ? (y/n)');
      if ($isMlist == 'y'){
        $message->getHeaders()->addTextHeader('List-unsubscribe', 'List <' . $returnPath . '>');  
      }
      
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
