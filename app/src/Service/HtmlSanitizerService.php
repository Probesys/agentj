<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class HtmlSanitizerService
{
    public function __construct(
        private HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    public function sanitize(string $html): string
    {
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
