<?php

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DurationExtension extends AbstractExtension
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('duration', [$this, 'formatDuration']),
        ];
    }

    public function formatDuration(int|float $seconds): string
    {
        if ($seconds >= 31536000) {
            $value = $seconds / 31536000;
            $unit = 'year';
        } elseif ($seconds >= 2592000) {
            $value = $seconds / 2592000;
            $unit = 'month';
        } elseif ($seconds >= 604800) {
            $value = $seconds / 604800;
            $unit = 'week';
        } elseif ($seconds >= 86400) {
            $value = $seconds / 86400;
            $unit = 'day';
        } elseif ($seconds >= 3600) {
            $value = $seconds / 3600;
            $unit = 'hour';
        } elseif ($seconds >= 60) {
            $value = $seconds / 60;
            $unit = 'minute';
        } else {
            $value = $seconds;
            $unit = 'second';
        }

        $formatted = rtrim(
            rtrim(number_format($value, 2, '.', ''), '0'),
            '.'
        );

        $key = 'Generics.labels.duration.' . $unit . ($value == 1 ? '' : '_plural');

        return $formatted . ' ' . $this->translator->trans($key);
    }
}
