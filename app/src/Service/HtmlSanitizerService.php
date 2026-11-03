<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class HtmlSanitizerService
{
    public function __construct(
        private HtmlSanitizerInterface $htmlSanitizer,
        #[Autowire(param: 'app.message_preview_max_length')]
        private int $maxLength,
    ) {
    }

    public function isTooLarge(string $html): bool
    {
        return strlen($html) > $this->maxLength;
    }

    public function sanitize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        if ($this->isTooLarge($html)) {
            throw new \LogicException('HTML is too large to be sanitized.');
        }

        $html = $this->htmlSanitizer->sanitize($html);

        $dom = new \DOMDocument();
        @$dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD
        );

        // Replace images with [IMAGE] and keep altname if exists
        foreach (iterator_to_array($dom->getElementsByTagName('img')) as $img) {
            $alt = trim($img->getAttribute('alt'));
            $replacement = $dom->createTextNode(
                $alt !== '' ? '[IMAGE] ' . $alt : '[IMAGE]'
            );
            $img->parentNode?->replaceChild($replacement, $img);
        }

        // Replace links while keeping their text
        foreach (iterator_to_array($dom->getElementsByTagName('a')) as $a) {
            $text = $a->textContent;
            $replacement = $dom->createTextNode('[URL] ' . $text);
            $a->parentNode?->replaceChild($replacement, $a);
        }

        $result = $dom->saveHTML();

        if ($result === false) {
            throw new RuntimeException('Failed to serialize sanitized HTML.');
        }

        return $result;
    }
}
