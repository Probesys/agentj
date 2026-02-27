<?php

namespace App\Util;

/**
 * Utility methods to ease building safe search requests.
 *
 * @phpstan-type SortParams array{
 *     sort: string,
 *     direction: string
 * }
 */
class Search
{
    /**
     * Transform some text to a MariaDB boolean fulltext search where each word is mandatory.
     *
     * @see https://mariadb.com/docs/server/ha-and-performance/optimization-and-tuning/optimization-and-indexes/full-text-indexes/full-text-index-overview
     *
     * @param string[] $excludeTerms
     */
    public static function textToMariadbBooleanSearch(string $text, array $excludeTerms = []): string
    {
        // Split the searchKey in terms tokens
        $text = trim($text);
        $terms = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Remove terms that must be excluded from the search.
        $terms = array_filter($terms, function ($term) use ($excludeTerms) {
            return !in_array($term, $excludeTerms);
        });

        // Sanitize the terms to perform a boolean search.
        $safeTerms = [];
        foreach ($terms as $term) {
            $safeTerm = preg_replace('/[+\-><()~*"@\.]+/', ' ', $term);
            $safeTerm = trim($safeTerm ?: '');
            $explodedSafeTerm = explode(' ', $safeTerm);
            $safeTerms = array_merge($safeTerms, $explodedSafeTerm);
        }

        // Remove stop words and words with less than 3 chars because searching
        // for them will return no results (as they are not indexed by MariaDB).
        $stopWords = self::getMariadbStopWords();
        $safeTerms = array_filter($safeTerms, function ($term) use ($stopWords) {
            return (
                !in_array($term, $stopWords) &&
                mb_strlen($term) >= 3
            );
        });

        // Add "+" to the start of each terms to make them mandatory
        $booleanTerms = array_map(fn($t) => '+' . $t, $safeTerms);

        return implode(' ', $booleanTerms);
    }

    /**
     * Return the list of MariaDB (InnoDB) stop words.
     *
     * These words are not indexed by MariaDB, so they must be removed from any boolean search strings.
     *
     * @see https://mariadb.com/docs/server/ha-and-performance/optimization-and-tuning/optimization-and-indexes/full-text-indexes/full-text-index-stopwords
     *
     * @return string[]
     */
    private static function getMariadbStopWords(): array
    {
        return [
            'a',
            'about',
            'an',
            'are',
            'as',
            'at',
            'be',
            'by',
            'com',
            'de',
            'en',
            'for',
            'from',
            'how',
            'i',
            'in',
            'is',
            'it',
            'la',
            'of',
            'on',
            'or',
            'that',
            'the',
            'this',
            'to',
            'was',
            'what',
            'when',
            'where',
            'who',
            'will',
            'with',
            'und',
            'the',
            'www',
        ];
    }

    /**
     * @param ?SortParams $sortParams
     * @param string[] $authorizedSortFields
     * @return ?SortParams
     */
    public static function sanitizeSortParams(
        ?array $sortParams,
        array $authorizedSortFields,
        string $defaultSortField,
    ): ?array {
        if (!$sortParams) {
            return null;
        }

        $sortField = $sortParams['sort'];
        $sortDirection = strtolower($sortParams['direction']);

        $sortFieldIsAuthorized = in_array($sortField, $authorizedSortFields);

        if (!$sortFieldIsAuthorized) {
            $sortField = $defaultSortField;
        }

        if ($sortDirection !== 'asc' && $sortDirection !== 'desc') {
            $sortDirection = 'desc';
        }

        return [
            'sort' => $sortField,
            'direction' => $sortDirection,
        ];
    }
}
