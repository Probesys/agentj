<?php

namespace App\Controller\Traits;

use Symfony\Component\Translation\TranslatorInterface;

trait ControllerCommonTrait {

    public function slugify($text) {
        $text = preg_replace('#[^\\pL\d]+#u', '-', $text);
        $text = trim($text, '-');
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        $text = strtolower($text);
        $text = preg_replace('#[^-\w]+#', '', $text);

        if (empty($text)) {
            return '';
        }
        return $text;
    }

}
