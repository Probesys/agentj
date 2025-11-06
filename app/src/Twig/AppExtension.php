<?php

namespace App\Twig;

use App\Entity\Groups;
use App\Entity\Msgs;
use App\Entity\User;
use App\Entity\Wblist;
use App\Service\LocalesService;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageService $messageService,
        private Security $security,
    ) {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('lcfirst', [$this, 'lcfirst']),
            new TwigFilter('stream_get_contents', [$this, 'streamGetcontent']),
            new TwigFilter('user_groups', [$this, 'getUserGroups']),
            new TwigFilter('wblist_is_overridden', [$this, 'wbListIsOverriden']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('message_release_token', [$this, 'messageReleaseToken']),
            new TwigFunction('get_original_user', [$this, 'getOriginalUser']),
            new TwigFunction('locales', [$this, 'locales']),
        ];
    }

    public function locales(): array
    {
        return LocalesService::SUPPORTED_LOCALES;
    }

    public function lcfirst(string $strInput): string
    {
        return lcfirst($strInput);
    }

    public function streamGetcontent(mixed $input): string
    {
        if (!is_resource($input)) {
            return $input;
        }

        $clearContent = stream_get_contents($input, -1, 0);
        if ($clearContent === false) {
            return '';
        }

        return $clearContent;
    }

    /**
     * @return string[] $groupsLabel
     */
    public function getUserGroups(int $userId): array
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return [];
        }

        $groups = $user->getGroups()->toArray();
        if (count($groups) === 0) {
            return [];
        }

        $groupsLabel = array_map(function (Groups $group) {
            return $group->getName();
        }, $groups);

        return $groupsLabel;
    }

    /**
     * Check if wblist is overriden by anotherOne with highter prioriry
     *
     * @param array<string, mixed> $wbInfo
     */
    public function wbListIsOverriden(array $wbInfo): bool
    {
        return $this->em->getRepository(Wblist::class)->wbListIsOverriden($wbInfo);
    }

    public function messageReleaseToken(Msgs $message, User $user): string
    {
        return $this->messageService->getReleaseToken($message, $user);
    }

    /**
     * Return the original user, or null if not impersonating.
     */
    public function getOriginalUser(): ?User
    {
        $token = $this->security->getToken();

        if ($token === null) {
            return null;
        }

        // Check if the token is a SwitchUserToken (impersonation active)
        if ($token instanceof SwitchUserToken) {
            $originalToken = $token->getOriginalToken();
            $originalUser = $originalToken->getUser();

            if ($originalUser instanceof User) {
                return $originalUser;
            }
        }

        return null;
    }
}
