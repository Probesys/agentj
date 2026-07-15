<?php

namespace App\Util;

class Text
{
    public static function slugify(string $text): string
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
