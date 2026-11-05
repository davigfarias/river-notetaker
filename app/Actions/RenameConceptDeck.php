<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeck;
use App\Support\Outcome;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\Log;

final readonly class RenameConceptDeck
{
    public function handle(int $deckId, string $name, int $accessTokenId): Outcome
    {
        try {
            $deck = ConceptDeck::query()->where('access_token_id', $accessTokenId)->findOrFail($deckId);

            $name = trim($name);
            $folded = TextNormalizer::fold($name);

            $duplicate = ConceptDeck::query()
                ->where('access_token_id', $accessTokenId)
                ->whereKeyNot($deck->id)
                ->pluck('name')
                ->contains(fn (string $existing) => TextNormalizer::fold($existing) === $folded);

            if ($duplicate) {
                return Outcome::failure(message: "Você já tem um deck chamado '{$name}'. Escolha outro nome.");
            }

            $deck->update(['name' => $name]);

            return Outcome::success(message: "Deck renomeado para '{$deck->name}'.", data: $deck);
        } catch (\Exception $e) {
            Log::error("Erro ao renomear deck de conceitos: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível renomear o deck.');
        }
    }
}
