<?php

namespace App\Service;

use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Cocur\Slugify\Slugify;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class UploaderNamer implements NamerInterface
{
    private $tokenStorage;
    
    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }
    
    public function name(FileInterface $file)
    {

      return 'logo.' . $file->getExtension();

    }
}
