<?php

namespace App\Twig;

use App\Entity\Groups;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension {

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
        $this->conn = $em->getConnection();
    }

    /**
     * 
     * @return array
     */
    public function getFilters() {
        return [
            new TwigFilter('lcfirst', [$this, 'lcfirst']),
            new TwigFilter('stream_get_contents', [$this, 'streamGetcontent']),
            new TwigFilter('user_groups', [$this, 'getUserGroups']),
        ];
    }

    public function lcfirst($strInput) {

        return lcfirst($strInput);
    }

    public function streamGetcontent($input): string {

        return stream_get_contents($input, -1, 0);
    }

    public function getUserGroups(int $userId): string {

        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return "";
        }

        $groups = $user->getGroups()->toArray();
        if (count($groups) === 0) {
            return "";
        }

        $groupsLabel = array_map(function (Groups $group) {
            return $group->getName();
        }
                , $groups);
        return implode(', ', $groupsLabel);

    }

}
