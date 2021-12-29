<?php

namespace App\Controller\Traits;

use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerCommonTrait
{

    public function slugify($text)
    {
        $text = preg_replace('#[^\\pL\d]+#u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        $text = preg_replace('#[^-\w]+#', '', $text);

        if (empty($text)) {
            return '';
        }
        return $text;
    }
}
