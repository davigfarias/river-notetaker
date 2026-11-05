<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeck;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class AddConceptsToDeck
{
    private const int MAX_PER_CALL = 500;

    /**
     * @param  array<int, mixed>  $conceptIds
     */
    public function handle(int $deckId, array $conceptIds, int $accessTokenId): Outcome
    {
        try {
            $deck = ConceptDeck::query()->where('access_token_id', $accessTokenId)->findOrFail($deckId);

            $ids = collect($conceptIds)
                ->filter(fn (mixed $id): bool => is_int($id) || (is_string($id) && ctype_digit($id)))
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->take(self::MAX_PER_CALL)
                ->values();

            $validIds = Concepts::query()->whereIn('id', $ids)->pluck('id');
            $now = now();

            $added = DB::transaction(fn (): int => DB::table('concept_deck_cards')->insertOrIgnore(
                $validIds->map(fn (int $id): array => [
                    'concept_deck_id' => $deck->id,
                    'concept_id' => $id,
                    'is_active' => true,
                    'is_starred' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            ));

            if ($added === 0) {
                return Outcome::success(message: 'Esses conceitos já estavam no deck.', data: 0);
            }

            return Outcome::success(
                message: $added === 1 ? '1 conceito adicionado.' : "{$added} conceitos adicionados.",
                data: $added,
            );
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar conceitos ao deck: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar os conceitos ao deck.');
        }
    }
}
