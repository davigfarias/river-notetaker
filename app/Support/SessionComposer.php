<?php

declare(strict_types=1);

namespace App\Support;

use App\DTO\SessionItem;
use Random\Randomizer;

/**
 * Embaralha e intercala os itens da sessão: sem dois seguidos da mesma categoria ou do
 * mesmo tipo, e sem mais de 3 seguidos do mesmo nível de Bloom, quando possível.
 * Determinístico: a aleatoriedade vem do Randomizer injetado.
 */
final readonly class SessionComposer
{
    private const MAX_SAME_LEVEL_IN_A_ROW = 3;

    public function __construct(private Randomizer $randomizer) {}

    /**
     * @param  array<int, SessionItem>  $items
     * @return array<int, SessionItem>
     */
    public function compose(array $items): array
    {
        $shuffled = $this->randomizer->shuffleArray($items);

        return $this->place([], $shuffled, fn (array $placed, SessionItem $i): bool => $this->levelOk($placed, $i) && $this->variesFromLast($placed, $i))
            ?? $this->place([], $shuffled, $this->levelOk(...))
            ?? $shuffled;
    }

    /**
     * Busca com retrocesso: tenta cada candidato aceito e desfaz se a sequência não fecha.
     * ponytail: exponencial no pior caso; ok para sessões de ~10 itens, trocar por heurística se crescerem.
     *
     * @param  array<int, SessionItem>  $placed
     * @param  array<int, SessionItem>  $pool
     * @param  callable(array<int, SessionItem>, SessionItem): bool  $accepts
     * @return array<int, SessionItem>|null
     */
    private function place(array $placed, array $pool, callable $accepts): ?array
    {
        if ($pool === []) {
            return $placed;
        }

        foreach ($pool as $key => $item) {
            if (! $accepts($placed, $item)) {
                continue;
            }

            $rest = $pool;
            unset($rest[$key]);

            $result = $this->place([...$placed, $item], $rest, $accepts);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param  array<int, SessionItem>  $placed
     */
    private function variesFromLast(array $placed, SessionItem $item): bool
    {
        $last = end($placed);

        return $last === false || ($last->groupKey !== $item->groupKey && $last->type !== $item->type);
    }

    /**
     * @param  array<int, SessionItem>  $placed
     */
    private function levelOk(array $placed, SessionItem $item): bool
    {
        $recent = array_slice($placed, -self::MAX_SAME_LEVEL_IN_A_ROW);

        return count($recent) < self::MAX_SAME_LEVEL_IN_A_ROW
            || array_any($recent, fn (SessionItem $r): bool => $r->type->bloomLevel() !== $item->type->bloomLevel());
    }
}
