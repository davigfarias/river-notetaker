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

    /**
     * Troca os marcadores do HTML já renderizado por chips. Blocos mermaid ficam de
     * fora: um <span> dentro do fonte quebraria o diagrama.
     */
    public static function chips(string $html): string
    {
        $parts = preg_split('#(<pre><code class="language-mermaid">.*?</code></pre>)#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $parts[$index] = preg_replace(
                    self::PATTERN,
                    '<span class="bg-primary/15 text-primary rounded-full px-2 py-0.5 text-sm">$1</span>',
                    $part
                );
            }
        }

        return implode('', $parts);
    }

    /**
     * Texto sem os marcadores, mantendo o rótulo: para tudo que não renderiza chip.
     */
    public static function strip(string $text): string
    {
        return (string) preg_replace(self::PATTERN, '$1', $text);
    }

    public static function key(string $label): string
    {
        return mb_substr(preg_replace('/\s+/u', '', TextNormalizer::fold($label)), 0, 120);
    }
}
