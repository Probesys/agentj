<?php

namespace App\Controller\Traits;

use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Wblist;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerWBListTrait
{

    public $wBListDomainActions;
    public $wBListUserActions;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->wBListDomainActions = [
        '' => '',
        $translator->trans('Entities.Domain.actions.blockAllMails') => "0",
        $translator->trans('Entities.Domain.actions.allowAllMails') => "W",
        ];

        $this->wBListUserActions = [
        '' => '',
        $translator->trans('Entities.Domain.actions.blockAllMails') => "B",
        $translator->trans('Entities.Domain.actions.allowAllMails') => "W",
        ];
    }

  /* UPDATE wblist from Group configuration (user,email, wb option ) */

    public function updatedWBListFromGroup($groupId)
    {

        $WblistRepository = $this->getDoctrine()->getManager()->getRepository(Wblist::class);
        $group = $this->getDoctrine()->getManager()->getRepository(Groups::class)->find($groupId);

        if ($group) {
            $result = $WblistRepository->deleteFromGroup($groupId);
            $UserRepository = $this->getDoctrine()->getManager()->getRepository(User::class);

          //Check if the group is active
            if ($result && $group->getActive()) {
                $WblistRepository->insertFromGroup($groupId);
                //update policy for all user from group
                $UserRepository->updatePolicyForGroup($groupId);
            } elseif (!$group->getActive()) {
              //update policy for all user of a group from domain
                $UserRepository->updatePolicyFromGroupToDomain($groupId);
            }
        }
    }
}
