<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Marcador de conceito no corpo da nota: {{no: Texto do conceito}}.
 */
final class MapConcepts
{
    public const PATTERN = '/\{\{no:\s*([^{}]+?)\s*\}\}/u';

    /**
     * @param  iterable<int, string|null>  $texts
     * @return array<string, string> chave (fold do rótulo, sem espaços) => rótulo, na ordem em que aparecem
     */
    public static function extract(iterable $texts): array
    {
        $concepts = [];

        foreach ($texts as $text) {
            preg_match_all(self::PATTERN, (string) $text, $matches);

            foreach ($matches[1] as $label) {
                $concepts[self::key($label)] ??= $label;
            }
        }

        return array_filter($concepts, fn (string $_, string $key) => $key !== '', ARRAY_FILTER_USE_BOTH);
    }

    public static function key(string $label): string
    {
        return mb_substr(preg_replace('/\s+/u', '', TextNormalizer::fold($label)), 0, 120);
    }
}
