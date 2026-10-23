<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Repository\MessageRecipientSearchRepository;

class ReportService
{
    public function __construct(
        private MessageRecipientSearchRepository $messageRecipientSearchRepository,
    ) {
    }

    /**
     * Retrieves messages that are not spam, or spam but under domain report spam level if defined.
     * It also contains mailing lists messages.
     *
     * @return MessageRecipient[]
     */
    public function getMessageRecipients(User $user): array
    {
        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $user->getDateLastReport(),
        )->getResult();

        $domain = $user->getDomain();
        $reportSpamLevel = $domain->getReportSpamLevel();

        if ($reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                maxSpamLevel: $reportSpamLevel,
                fromDate: $user->getDateLastReport(),
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        return $messageRecipients;
    }

    /**
     * Retrieves standard messages.
     *
     * Standard messages are messages with:
     *   - status UNTREATED
     *   - not considered as spam (score <= 0 or <= to the domain report spam level if defined)
     *   - not sent by a mailing list.
     *
     * @return MessageRecipient[]
     */
    public function getStandardMessageRecipients(User $user): array
    {
        $domain = $user->getDomain();
        $reportSpamLevel = $domain->getReportSpamLevel();

        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $user->getDateLastReport(),
            getMailingList: false,
        )->getResult();

        if ($reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                fromDate: $user->getDateLastReport(),
                getMailingList: false,
                maxSpamLevel:$reportSpamLevel,
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        return $messageRecipients;
    }

    /**
     * Retrieves messages sent by mailing lists.
     *
     * List messages are messages with:
     *   - status UNTREATED
     *   - not considered as spam (score <= 0 or <= to the domain report spam level if defined)
     *   - sent by a mailing list
     *
     * @return MessageRecipient[]
     */
    public function getListMessageRecipients(User $user): array
    {
        $domain = $user->getDomain();
        $reportSpamLevel = $domain->getReportSpamLevel();

        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $user->getDateLastReport(),
            getMailingList: true,
            maxSpamLevel: $reportSpamLevel,
        )->getResult();

        if ($reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                fromDate: $user->getDateLastReport(),
                getMailingList: true,
                maxSpamLevel: $reportSpamLevel,
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        return $messageRecipients;
    }

    /**
     * Retrieves spam messages.
     *
     * Spam messages are message with:
     *   - spam level > 0 or > to the domain report spam level if defined
     *   - not sent by a mailing list
     *
     * @return MessageRecipient[]
     */
    public function getSpamMessageRecipients(User $user): array
    {
        $domain = $user->getDomain();
        $reportSpamLevel = $domain->getReportSpamLevel();

        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $user->getDateLastReport(),
            minSpamLevel: $reportSpamLevel,
        )->getResult();

        if ($reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                fromDate: $user->getDateLastReport(),
                minSpamLevel: $reportSpamLevel,
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        return $messageRecipients;
    }
}
