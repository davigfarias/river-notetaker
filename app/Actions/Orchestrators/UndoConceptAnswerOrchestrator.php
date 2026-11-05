<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ApplyConceptReview;
use App\DTO\ConceptRecallCounters;
use App\Models\ConceptReview;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reverts one `AnswerConceptCardOrchestrator` call using the snapshot the
 * study page kept: deletes the review (only if id, token and concept match)
 * and restores the counters.
 */
final readonly class UndoConceptAnswerOrchestrator
{
    public function __construct(private ApplyConceptReview $applyReview) {}

    public function handle(
        int $accessTokenId,
        int $conceptId,
        ?int $reviewId,
        int $previousAcedCount,
        int $previousMissedCount,
        ?string $previousLastReviewedAt,
    ): Outcome {
        try {
            DB::transaction(function () use ($accessTokenId, $conceptId, $reviewId, $previousAcedCount, $previousMissedCount, $previousLastReviewedAt): void {
                if ($reviewId !== null) {
                    ConceptReview::query()
                        ->whereKey($reviewId)
                        ->where('access_token_id', $accessTokenId)
                        ->where('concept_id', $conceptId)
                        ->delete();
                }

                // ponytail: absolute snapshot overwrites answers given meanwhile in another tab; relative decrement if that ever matters.
                $this->applyReview->handle(
                    $accessTokenId,
                    $conceptId,
                    new ConceptRecallCounters($previousAcedCount, $previousMissedCount),
                    $previousLastReviewedAt !== null ? CarbonImmutable::parse($previousLastReviewedAt) : null,
                );
            });

            return Outcome::success();
        } catch (\Exception $e) {
            Log::error("Erro ao desfazer resposta de conceito: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível desfazer a resposta.');
        }
    }
}
