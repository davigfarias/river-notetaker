<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mapa mental livre de uma nota (coluna notes.mind_map), montado no canvas de
 * resources/js/mapa-da-nota.js. O cliente manda o mapa inteiro; aqui ele é
 * limpo antes de gravar, porque nada que vem do navegador é confiável.
 *
 * @phpstan-type MindMapNode array{key: string, label: string, x: float, y: float}
 * @phpstan-type MindMapEdge array{from: string, to: string, bend: float}
 * @phpstan-type MindMapData array{nodes: list<MindMapNode>, edges: list<MindMapEdge>}
 */
final class MindMap
{
    public const MAX_NODES = 500;

    public const MAX_EDGES = 1000;

    public const MAX_LABEL = 200;

    private const COORDINATE_LIMIT = 100_000;

    private const BEND_LIMIT = 2000;

    /**
     * @return MindMapData
     */
    public static function empty(): array
    {
        return ['nodes' => [], 'edges' => []];
    }

    /**
     * Descarta nó sem texto, chave inválida ou repetida, seta para nó inexistente,
     * seta repetida ou de um nó para ele mesmo; limita tamanhos e coordenadas.
     *
     * @return MindMapData
     */
    public static function sanitize(mixed $map): array
    {
        if (! is_array($map)) {
            return self::empty();
        }

        $nodes = [];

        foreach (array_slice(self::listOf($map['nodes'] ?? null), 0, self::MAX_NODES) as $node) {
            $key = is_array($node) && is_string($node['key'] ?? null) ? $node['key'] : '';
            $label = is_array($node) && is_string($node['label'] ?? null)
                ? Str::limit(trim(preg_replace('/\s+/u', ' ', $node['label']) ?? ''), self::MAX_LABEL, '')
                : '';

            if ($label === '' || ! preg_match('/^[a-z0-9]{1,24}$/', $key) || isset($nodes[$key])) {
                continue;
            }

            $nodes[$key] = [
                'key' => $key,
                'label' => $label,
                'x' => self::clamp($node['x'] ?? 0, self::COORDINATE_LIMIT),
                'y' => self::clamp($node['y'] ?? 0, self::COORDINATE_LIMIT),
            ];
        }

        $edges = [];

        foreach (self::listOf($map['edges'] ?? null) as $edge) {
            $from = is_array($edge) && is_string($edge['from'] ?? null) ? $edge['from'] : '';
            $to = is_array($edge) && is_string($edge['to'] ?? null) ? $edge['to'] : '';

            if ($from === $to || ! isset($nodes[$from], $nodes[$to]) || isset($edges["{$from}>{$to}"])) {
                continue;
            }

            $edges["{$from}>{$to}"] = ['from' => $from, 'to' => $to, 'bend' => self::clamp($edge['bend'] ?? 0, self::BEND_LIMIT)];

            if (count($edges) >= self::MAX_EDGES) {
                break;
            }
        }

        return ['nodes' => array_values($nodes), 'edges' => array_values($edges)];
    }

    /**
     * Textos dos nós, um por linha, para a busca e o resumo por IA.
     */
    public static function text(mixed $map): string
    {
        return implode("\n", array_column(self::sanitize($map)['nodes'], 'label'));
    }

    /**
     * @return list<mixed>
     */
    private static function listOf(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    private static function clamp(mixed $value, int $limit): float
    {
        return is_numeric($value) ? (float) max(-$limit, min($limit, round((float) $value, 1))) : 0.0;
    }
}
