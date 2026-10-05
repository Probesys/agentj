<?php

namespace App\Twig;

use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Model\UnsubscribeMethods;
use App\Service\ListUnsubscribeService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ListUnsubscribeExtension extends AbstractExtension
{
    public function __construct(
        private ListUnsubscribeService $listUnsubscribeService,
        private Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('list_unsubscribe_methods', [$this, 'getUnsubscribeMethods']),
            new TwigFunction('last_unsubscribe_request', [$this, 'getLastUnsubscribeRequest']),
        ];
    }

    public function getUnsubscribeMethods(MessageRecipient $messageRecipient): ?UnsubscribeMethods
    {
        if (!$this->security->isGranted('UNSUBSCRIBE', $messageRecipient)) {
            return null;
        }

        return $this->listUnsubscribeService->getUnsubscribeMethods($messageRecipient);
    }

    public function getLastUnsubscribeRequest(MessageRecipient $messageRecipient): ?UnsubscribeRequest
    {
        return $this->listUnsubscribeService->getLastRequest($messageRecipient);
    }
}
