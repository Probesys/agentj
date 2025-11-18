<?php

namespace App\Service;

use App\Entity\Abris;
use App\Entity\Dysfonctionnement;
use App\Entity\User;
use App\Entity\UploadedDocument;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(private string $targetDirectory)
    {
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileExtension = $file->guessExtension();
        if (!$fileExtension) {
            return "";
        }

        $fullFilename = $originalFilename . '.' . $fileExtension;

        try {
            $file->move($this->targetDirectory, $fullFilename);
            return $fullFilename;
        } catch (FileException $e) {
            return "";
        }
    }
}
