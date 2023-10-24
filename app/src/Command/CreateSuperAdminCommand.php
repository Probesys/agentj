<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;



class CreateSuperAdminCommand extends Command
{

    private $passwordEncoder;
    private $em;
    

    protected static $defaultName = 'agentj:create-super-admin';

    public function __construct(UserPasswordHasherInterface $encoder, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->passwordEncoder = $encoder;
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Create the first super administrator for the application. If user exist the password will be updated');
        $this->addArgument('userName', InputArgument::REQUIRED, 'Super admin login')
            ->addArgument('password', InputArgument::REQUIRED, 'Super admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        $io = new SymfonyStyle($input, $output);
        $isNewUser = false;
        $userName = $input->getArgument('userName');
        $password = $input->getArgument('password');
        
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $userName]);
        if (!$user) {
            $user = new User();
            $user->setRoles('["ROLE_SUPER_ADMIN"]');

            $user->setUsername($userName);
            $io->note('User ' . $userName . ' will be created');
            $isNewUser = true;
        } else {
            $io->note("User " . $userName . " will be  updated");
        }

        $this->em->persist($user);

        $encoded = $this->passwordEncoder->hashPassword($user, $input->getArgument('password'));

        $user->setPassword($encoded);

        $this->em->flush();
        if ($isNewUser) {
            $io->success("The user " . $userName . " has been successfully created");
        } else {
            $io->success("The user " . $userName . " has been successfully updated");
        }
        
        return Command::SUCCESS;
    }
}
