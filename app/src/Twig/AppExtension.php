<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    /**
     * 
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('lcfirst', [$this, 'lcfirst']),
            new TwigFilter('stream_get_contents', [$this, 'streamGetcontent']),
        ];
    }

    public function lcfirst($strInput)
    {

        return lcfirst($strInput);
    }
    
    public function streamGetcontent($input): string
    {

        return stream_get_contents($input, -1, 0);
    }    
}
