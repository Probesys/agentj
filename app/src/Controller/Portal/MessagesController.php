<?php

namespace App\Controller\Portal;

use App\Entity;
use App\Repository;
use App\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MessagesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Service\CryptEncryptService $cryptEncryptService,
        private Service\LogService $logService,
        private Service\MessageService $messageService,
        private Repository\MsgrcptRepository $msgrcptRepository,
        private Repository\MsgsRepository $msgsRepository,
    ) {
    }

    #[Route(path: '/portal/{token}/message/{partitionTag}/{mailId}/{recipientId}/authorized', name: 'portal_message_authorized')]
    public function authorized(
        string $token,
        int $partitionTag,
        string $mailId,
        int $recipientId,
        Request $request,
    ): Response {
        $isConnected = $this->getUser() !== null;

        if ($isConnected) {
            return $this->redirectToRoute('message_authorized', [
                'partitionTag' => $partitionTag,
                'mailId' => $mailId,
                'rid' => $recipientId,
            ]);
        }

        $user = $this->getUserFromToken($token);

        if (!$user) {
            throw $this->createNotFoundException('The token is invalid.');
        }

        $message = $this->msgsRepository->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->msgrcptRepository->findOneByMessageAndRid($message, $recipientId);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        $this->checkMailAccess($user, $messageRecipient);

        if ($this->messageService->authorize($message, [$messageRecipient], Entity\Wblist::WBLIST_TYPE_USER)) {
            $this->logService->addLog('authorized', $mailId);
        }

        return $this->render('portal/messages/authorized.html.twig');
    }

    #[Route(path: '/portal/{token}/message/{partitionTag}/{mailId}/{recipientId}/restore', name: 'portal_message_restore')]
    public function restore(
        string $token,
        int $partitionTag,
        string $mailId,
        int $recipientId,
    ): Response {
        $isConnected = $this->getUser() !== null;

        if ($isConnected) {
            return $this->redirectToRoute('message_restore', [
                'partitionTag' => $partitionTag,
                'mailId' => $mailId,
                'rid' => $recipientId,
            ]);
        }

        $user = $this->getUserFromToken($token);

        if (!$user) {
            throw $this->createNotFoundException('The token is invalid.');
        }

        $message = $this->msgsRepository->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->msgrcptRepository->findOneByMessageAndRid($message, $recipientId);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        $this->checkMailAccess($user, $messageRecipient);

        if ($this->messageService->restore($message, $messageRecipient)) {
            $this->logService->addLog('restore', $mailId);
        }

        return $this->render('portal/messages/restore.html.twig');
    }

    private function checkMailAccess(Entity\User $user, Entity\Msgrcpt $messageRecipient): void
    {
        $listAliases = $this->em->getRepository(Entity\User::class)->getListAliases($user);
        $accessibleRecipientEmails = array_map(function ($item) {
            return stream_get_contents($item->getEmail(), -1, 0);
        }, $listAliases);
        $accessibleRecipientEmails = array_merge([$user->getEmailFromRessource()], $accessibleRecipientEmails);

        if (!in_array($messageRecipient->getRid()->getEmailClear(), $accessibleRecipientEmails)) {
            throw new AccessDeniedException();
        }
    }

    private function getUserFromToken(string $token): ?Entity\User
    {
        list($expiry, $data) = $this->cryptEncryptService->decrypt($token);

        if ($expiry < time()) {
            return null;
        }

        $userId = (int) $data;
        return $this->em->getRepository(Entity\User::class)->find($userId);
    }
}
