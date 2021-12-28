<?php

namespace App\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class OneUpUploaderListener
{

  /**
   * Upload the logo, resize it and save in configuration
   * @param PostPersistEvent $event
   * @return type
   */
    public function onUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();
        $response['success'] = false;

        $response = $event->getResponse();
        $baseUrl = $request->getScheme() . '://' . $request->getHost() . $request->getBaseUrl();


        $size = getimagesize($event->getFile());
        $width = 170;
        $height = 45;



        $logoFileName = $event->getFile()->getPath() . "/" . $event->getFile()->getFileName();

        switch (exif_imagetype($logoFileName)) {
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($event->getFile()->getPath() . "/" . $event->getFile()->getFileName());
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($event->getFile()->getPath() . "/" . $event->getFile()->getFileName());
                break;
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($event->getFile()->getPath() . "/" . $event->getFile()->getFileName());
                break;
        }

        $dst = imagecreatetruecolor($width, $height);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
        imagedestroy($src);
        imagepng($dst, $event->getFile()->getPath() . "/logo.png"); // adjust format as needed
        imagedestroy($dst);


        $response['success'] = true;
        $response['url'] = str_replace('index.php', '', $baseUrl) . 'files/upload/' . $event->getFile()->getFileName();
        $response['filename'] = $event->getFile()->getFileName();
        $response['filesize'] = $event->getFile()->getSize();

        return $response;
    }
}
