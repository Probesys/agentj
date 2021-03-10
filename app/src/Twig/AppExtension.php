<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('lcfirst', [$this, 'lcfirst']),
        ];
    }

    public function lcfirst($strInput)
    {

        return lcfirst($strInput);
    }
}
