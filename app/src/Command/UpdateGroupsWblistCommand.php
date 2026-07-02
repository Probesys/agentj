<?php

namespace App\Command;

use App\Entity\GroupsWblist;
use App\Entity\Mailaddr;
use App\Repository\GroupRepository;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'agentj:update-groups-wblist',
    description: 'setPriority to groups that does not have and generate rules. Use when upgrade from  1.6.1 and before',
)]
class UpdateGroupsWblistCommand extends Command
{
    public function __construct(
        private GroupRepository $groupRepository,
        private EntityManagerInterface $em,
        private GroupService $groupService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->setGroupPriority();

        $activeGroups = $this->groupRepository->findBy([
            'active' => true
        ]);

        $rootMailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
        foreach ($activeGroups as $group) {
            $groupsWblist = $this->em->getRepository(GroupsWblist::class)->findOneBy(([
                'mailaddr' => $rootMailaddr,
                'group' => $group,
            ]));
            if (!$groupsWblist) {
                $groupsWblist = new GroupsWblist();
                $groupsWblist->setMailaddr($rootMailaddr);
            }
            $groupsWblist->setGroup($group);
            $groupsWblist->setWb($group->getWb());
            $this->em->persist($groupsWblist);
        }
        $this->em->flush();
        $this->groupService->updateWblist();

        return Command::SUCCESS;
    }

    /**
     * Update the group priority that does not have priority
     * @return void
     */
    private function setGroupPriority(): void
    {
        $listGroupWithNoPriority = $this->groupRepository->findBy(['priority' => null]);
        foreach ($listGroupWithNoPriority as $group) {
            $maxPriority = $this->groupRepository->getMaxPriorityforDomain($group->getDomain());
            $group->setPriority($maxPriority + 1);
            $this->em->persist($group);
            $this->em->flush();
        }
    }
}
