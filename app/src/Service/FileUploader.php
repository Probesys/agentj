<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Move the uploaded file to the target directory and return its new name,
     * or an empty string on failure.
     *
     * The name gets a random suffix so that it cannot be guessed, and a file
     * cannot replace another one with the same original name.
     */
    public function upload(UploadedFile $file): string
    {
        $fileExtension = $file->guessExtension();
        if (!$fileExtension) {
            return '';
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower()->truncate(50);
        $fullFilename = sprintf('%s-%s.%s', $safeFilename, bin2hex(random_bytes(8)), $fileExtension);

        try {
            $file->move($this->targetDirectory, $fullFilename);
            return $fullFilename;
        } catch (FileException) {
            return '';
        }
    }

    public function remove(string $filename): void
    {
        $path = $this->targetDirectory . basename($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }
}
