<?php

namespace App\Command;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'agentj:api-key:generate',
    description: 'Generate (or regenerate) the API key used to authenticate /api/users/import for a domain',
)]
class GenerateApiKeyCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('domain', InputArgument::REQUIRED, 'The domain name (e.g. example.com)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domainName = strtolower((string) $input->getArgument('domain'));

        $domain = $this->em->getRepository(Domain::class)->findOneBy(['domain' => $domainName]);
        if (!$domain) {
            $io->error("Domain '$domainName' not found.");

            return Command::FAILURE;
        }

        $apiKey = bin2hex(random_bytes(32));
        $domain->setApiKeyHash(hash('sha256', $apiKey));
        $this->em->flush();

        $io->success("API key generated for domain '$domainName'.");
        $io->warning('This key is only shown once and cannot be retrieved again. Store it securely.');
        $io->writeln('<comment>API key:</comment> ' . $apiKey);

        return Command::SUCCESS;
    }
}
