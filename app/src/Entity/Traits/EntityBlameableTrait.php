<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait EntityBlameableTrait
{
    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * Set createdBy.
     *
     * @param \App\Entity\User $createdBy
     *
     */
    public function setCreatedBy(\App\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return \App\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

}
