<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Captcha\Bundle\CaptchaBundle\Validator\Constraints as CaptchaAssert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\CaptchaRepository")
 */
class Captcha
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
    * @CaptchaAssert\ValidCaptcha(
    *      message = "CAPTCHA validation failed, try again."
    * )
    */
    protected $captchaCode;

 
    
    public function __toString(): string
    {
        return $this->name;
    }      
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    
      public function getCaptchaCode()
  {
    return $this->captchaCode;
  }

  public function setCaptchaCode($captchaCode)
  {
    $this->captchaCode = $captchaCode;
  }   
}
