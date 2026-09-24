<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingNote;
use App\Models\ReviewLog;
use App\Support\Outcome;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registra o julgamento de uma revisão de anotação de leitura e reagenda em
 * dias corridos. "Lembrei" sobe um degrau da escada; "travei" devolve a
 * anotação ao primeiro degrau.
 */
final readonly class RecordReadingNoteReview
{
    public function handle(int $readingNoteId, int $accessTokenId, bool $recalled, ?CarbonImmutable $today = null): Outcome
    {
        try {
            $today = ($today ?? CarbonImmutable::now())->startOfDay();

            $note = ReadingNote::query()
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->find($readingNoteId);

            if (! $note instanceof ReadingNote) {
                return Outcome::failure('Anotação não encontrada.');
            }

            $stageBefore = ReviewSchedule::normalizeStage($note->review_stage ?? 0);
            $stageAfter = ReviewSchedule::nextStage($stageBefore, $recalled);
            $dueAt = ReviewSchedule::dueDateForDaily($stageAfter, $today);
            $consolidated = ReviewSchedule::isConsolidated($stageAfter);

            DB::transaction(function () use ($note, $stageBefore, $stageAfter, $dueAt, $consolidated, $recalled, $today, $accessTokenId): void {
                ReviewLog::create([
                    'reviewable_type' => $note->getMorphClass(),
                    'reviewable_id' => $note->id,
                    'access_token_id' => $accessTokenId,
                    'recalled' => $recalled,
                    'stage_before' => $stageBefore,
                    'stage_after' => $stageAfter,
                    'due_at' => $note->next_review_at,
                    'reviewed_at' => $today->setTimeFrom(CarbonImmutable::now()),
                ]);

                $note->forceFill([
                    'review_stage' => $stageAfter,
                    'next_review_at' => $dueAt?->toDateString(),
                    'consolidated_at' => $consolidated ? CarbonImmutable::now() : null,
                ])->saveQuietly();
            });

            if ($consolidated) {
                return Outcome::success('Anotação consolidada: ela sai da fila de revisão.');
            }

            return $recalled
                ? Outcome::success('Revisão registrada. Próxima cobrança agendada.')
                : Outcome::success('Sem problema: ela volta amanhã.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível registrar a revisão.');
        }
    }
}
