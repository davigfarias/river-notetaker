<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindConceptCardsToStudy;
use App\DTO\ConceptStudySessionData;
use App\Models\ConceptDeck;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class StartConceptStudySessionOrchestrator
{
    public function __construct(private FindConceptCardsToStudy $findCards) {}

    public function handle(int $accessTokenId, ConceptDeck $deck): Outcome
    {
        try {
            if ($deck->access_token_id !== $accessTokenId) {
                return Outcome::failure(message: 'Deck não encontrado.');
            }

            $cards = $this->findCards->handle($accessTokenId, $deck->id);

            return Outcome::success(data: new ConceptStudySessionData(
                deckName: $deck->name,
                cardIds: $cards->pluck('id')->all(),
            ));
        } catch (\Exception $e) {
            Log::error("Erro ao iniciar estudo de conceitos: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar o estudo.');
        }
    }
}
