<?php

namespace App\Twig;

use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\Message;
use App\Entity\User;
use App\Entity\SenderRule;
use App\Service\LocaleService;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Asset\Packages;
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
        private Packages $packages,
        private string $uploadDirectory,
        private string $defaultLogoFilename,
    ) {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('lcfirst', [$this, 'lcfirst']),
            new TwigFilter('user_groups', [$this, 'getUserGroups']),
            new TwigFilter('sender_rule_is_overridden', [$this, 'senderRuleIsOverridden']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('message_release_token', [$this, 'messageReleaseToken']),
            new TwigFunction('get_original_user', [$this, 'getOriginalUser']),
            new TwigFunction('domain_logo_asset', [$this, 'domainLogoAsset']),
            new TwigFunction('locales', [$this, 'locales']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function locales(): array
    {
        return LocaleService::SUPPORTED_LOCALES;
    }

    public function lcfirst(string $strInput): string
    {
        return lcfirst($strInput);
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

        $groupsLabel = array_map(function (Group $group) {
            return $group->getName();
        }, $groups);

        return $groupsLabel;
    }

    /**
     * Check if sender rule is overridden by anotherOne with higher priority
     *
     * @param SenderRule $senderRule
     */
    public function senderRuleIsOverridden(SenderRule $senderRule): bool
    {
        return $this->em->getRepository(SenderRule::class)->senderRuleIsOverridden($senderRule);
    }

    public function messageReleaseToken(Message $message, User $user): string
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
                // The user carried by the original token comes from the
                // serialized session, not from Doctrine, so it isn't
                // hydrated (its collections, e.g. ownedSharedBoxes, would
                // appear empty). Reload it from the database.
                return $this->em->getRepository(User::class)->find($originalUser->getId());
            }
        }

        return null;
    }

    /**
     * Returns the domain logo asset path if the domain has a logo uploaded.
     * If not, returns the default logo asset path.
     * If the domain is null/undefined (falsey), returns the default logo asset path.
     */
    public function domainLogoAsset(?Domain $domain): string
    {
        // default logo filename set in service.yaml
        $defaultLogoAsset = $this->packages->getUrl('img/' . $this->defaultLogoFilename);

        // if the domain is null/undefined
        if (!$domain) {
            return $defaultLogoAsset;
        }

        $domainLogo = $domain->getLogo();

        // if the domain has no logo of the file doesn't exist
        // default logo upload directory also set in service.yaml
        if (!$domainLogo || !file_exists($this->uploadDirectory . $domainLogo)) {
            return $defaultLogoAsset;
        }

        return $this->packages->getUrl('files/upload/' . $domainLogo);
    }
}
