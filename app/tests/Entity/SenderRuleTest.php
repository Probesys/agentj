<?php

namespace App\Tests\Entity;

use App\Entity\SenderRule;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SenderRuleTest extends KernelTestCase
{
    use Factories;

    public function testCopyCopiesSenderRuleAttributes(): void
    {
        $user = UserFactory::new()->user()->withoutPersisting()->create();
        $otherUser = UserFactory::new()->user()->withoutPersisting()->create();
        $senderRuleAddress = RuleAddressFactory::new()->withoutPersisting()->create();
        $group = GroupFactory::new()->withoutPersisting()->create();
        $originalSenderRule = new SenderRule($user, $senderRuleAddress);
        $originalSenderRule->setPriority(SenderRule::PRIORITY_USER);
        $originalSenderRule->setType(SenderRule::TYPE_USER);
        $originalSenderRule->setWbRule('accept');
        $originalSenderRule->setGroup($group);

        $senderRule = SenderRule::copy($otherUser, $originalSenderRule);

        self::assertSame($otherUser, $senderRule->getUser());
        self::assertSame($senderRuleAddress, $senderRule->getSenderRuleAddress());
        self::assertSame($originalSenderRule->getPriority(), $senderRule->getPriority());
        self::assertSame($originalSenderRule->getWb(), $senderRule->getWb());
        self::assertSame($originalSenderRule->getType(), $senderRule->getType());
        self::assertSame($originalSenderRule->getGroup(), $senderRule->getGroup());
    }
}
