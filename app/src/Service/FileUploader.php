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

//use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader {



    private $targetDirectory;

    public function __construct($targetDirectory, EntityManager $em) {

        $this->targetDirectory = $targetDirectory;

    }

    public function upload(UploadedFile $file) {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        try {          
            $file->move($this->targetDirectory , $originalFilename);   
            return $originalFilename;
        } catch (FileException $e) {
            return "";
        }
       
    }
    
    public function setTargetDirectory($dirname) {
        $this->targetDirectory = $this->targetDirectory.$dirname;
        return $this;
    }

}
