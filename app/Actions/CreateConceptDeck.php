<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeck;
use App\Support\Outcome;
use App\Support\TextNormalizer;
use Illuminate\Support\Facades\Log;

final readonly class CreateConceptDeck
{
    public function handle(string $name, int $accessTokenId): Outcome
    {
        try {
            $name = trim($name);
            $folded = TextNormalizer::fold($name);

            $duplicate = ConceptDeck::query()
                ->where('access_token_id', $accessTokenId)
                ->pluck('name')
                ->contains(fn (string $existing) => TextNormalizer::fold($existing) === $folded);

            if ($duplicate) {
                return Outcome::failure(message: "Você já tem um deck chamado '{$name}'. Escolha outro nome.");
            }

            $deck = ConceptDeck::create(['access_token_id' => $accessTokenId, 'name' => $name]);

            return Outcome::success(message: "Deck '{$deck->name}' criado.", data: $deck);
        } catch (\Exception $e) {
            Log::error("Erro ao criar deck de conceitos: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível criar o deck.');
        }
    }
}
