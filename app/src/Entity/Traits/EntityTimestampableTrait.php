<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait EntityTimestampableTrait
{
    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;


    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }


    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

}
