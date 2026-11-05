<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ApplyConceptReview;
use App\Actions\CalculateConceptRecallCounters;
use App\Actions\RecordConceptReview;
use App\DTO\ConceptAnswerData;
use App\Enums\ConceptReviewResult;
use App\Models\ConceptRecallCounter;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class AnswerConceptCardOrchestrator
{
    public function __construct(
        private CalculateConceptRecallCounters $calculateRecallCounters,
        private ApplyConceptReview $applyReview,
        private RecordConceptReview $recordReview,
    ) {}

    public function handle(int $accessTokenId, int $conceptId, ConceptReviewResult $result): Outcome
    {
        try {
            $now = CarbonImmutable::now();

            $data = DB::transaction(function () use ($accessTokenId, $conceptId, $result, $now): ConceptAnswerData {
                $current = ConceptRecallCounter::createOrFirst(
                    ['access_token_id' => $accessTokenId, 'concept_id' => $conceptId],
                    ['aced_count' => 0, 'missed_count' => 0],
                );

                $previousAced = $current->aced_count;
                $previousMissed = $current->missed_count;
                $previousLast = $current->last_reviewed_at?->toIso8601String();

                $counters = $this->calculateRecallCounters->handle($previousAced, $previousMissed, $result);

                $this->applyReview->handle($accessTokenId, $conceptId, $counters, $now);
                $review = $this->recordReview->handle($accessTokenId, $conceptId, $result, $now);

                return new ConceptAnswerData($review->id, $previousAced, $previousMissed, $previousLast);
            });

            return Outcome::success(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao registrar resposta de conceito: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar sua resposta. Tente de novo.');
        }
    }
}
