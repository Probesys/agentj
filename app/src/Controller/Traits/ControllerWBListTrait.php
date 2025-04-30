<?php

namespace App\Controller\Traits;

use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Wblist;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerWBListTrait {


    /**
     * @return array<string, string>
     */
    public function getWBListDomainActions(): array {
        return [
            '' => '',
            $this->translator->trans('Entities.Domain.actions.blockAllMails') => "0",
            $this->translator->trans('Entities.Domain.actions.allowAllMails') => "W",
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getWBListUserActions(): array {
        return [
            '' => '',
            $this->translator->trans('Entities.Domain.actions.blockAllMails') => "B",
            $this->translator->trans('Entities.Domain.actions.allowAllMails') => "W",
        ];
    }
}
