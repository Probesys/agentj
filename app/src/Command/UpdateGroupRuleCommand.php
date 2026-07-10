<?php

namespace App\Command;

use App\Entity\GroupRule;
use App\Entity\RuleAddress;
use App\Repository\GroupRepository;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'agentj:update-groups-sender-rule',
    description: 'setPriority to groups that does not have and generate rules. Use when upgrade from  1.6.1 and before',
)]
class UpdateGroupRuleCommand extends Command
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
        $this->setGroupPriority();

        $activeGroups = $this->groupRepository->findBy([
            'active' => true
        ]);

        $rootRuleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy((['email' => '@.']));
        foreach ($activeGroups as $group) {
            $groupRule = $this->em->getRepository(GroupRule::class)->findOneBy(([
                'ruleAddress' => $rootRuleAddress,
                'group' => $group,
            ]));
            if (!$groupRule) {
                $groupRule = new GroupRule();
                $groupRule->setRuleAddress($rootRuleAddress);
            }
            $groupRule->setGroup($group);
            $groupRule->setWb($group->getWb());
            $this->em->persist($groupRule);
        }
        $this->em->flush();
        $this->groupService->updateSenderRule();

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
