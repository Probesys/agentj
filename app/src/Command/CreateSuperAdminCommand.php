<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;

class CreateSuperAdminCommand extends ContainerAwareCommand {

  private $passwordEncoder;

  private const DEFAULT_USERNAME = 'admin';

  protected static $defaultName = 'agentj:create-super-admin';

  public function __construct(UserPasswordEncoderInterface $encoder) {
    parent::__construct();
    $this->passwordEncoder = $encoder;
  }

  protected function configure() {
    $this->setDescription('Create the first super administrator for the application. If user exist the password will be updated');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager();
    $isNewUser = false;
    $user = $em->getRepository(User::class)->findOneBy(['username' => self::DEFAULT_USERNAME]);
    if (!$user) {
      $user = new User();
      $user->setRoles('["ROLE_SUPER_ADMIN"]');

      $user->setUsername(self::DEFAULT_USERNAME);
      $io->note('User ' . self::DEFAULT_USERNAME . ' will be created');
      $isNewUser = true;
    } else {
      $io->note("User " . self::DEFAULT_USERNAME . " will be  updated");
    }

    $em->persist($user);


    $passWordMatch = false;
    while (!$passWordMatch) {
      $clearPass1 = $io->askHidden("Please provide a password for the user \"" . self::DEFAULT_USERNAME . "\"");
      $clearPass2 = $io->askHidden("Please confirm this password");
      if ($clearPass1 != $clearPass2) {
        $io->error("The passwords don't match. Try again please ");
      } else {
        $passWordMatch = true;
      }
    }
    $encoded = $this->passwordEncoder->encodePassword($user, $clearPass1);

    $user->setPassword($encoded);

    $em->flush();
    if ($isNewUser) {
      $io->success("The user " . self::DEFAULT_USERNAME . " has been successfully created");
    } else {
      $io->success("The user " . self::DEFAULT_USERNAME . " has been successfully updated");
    }
  }

}
