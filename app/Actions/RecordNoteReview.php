<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Notes;
use App\Models\ReviewLog;
use App\Support\Outcome;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registra o julgamento de uma revisão e reagenda a nota. "Lembrei" sobe um
 * degrau da escada; "travei" devolve a nota ao primeiro degrau.
 */
final readonly class RecordNoteReview
{
    public function handle(int $noteId, int $accessTokenId, bool $recalled, ?CarbonImmutable $today = null): Outcome
    {
        try {
            $today = ($today ?? CarbonImmutable::now())->startOfDay();

            $note = Notes::with('discipline')
                ->where('access_token_id', $accessTokenId)
                ->find($noteId);

            if (! $note instanceof Notes) {
                return Outcome::failure('Nota não encontrada.');
            }

            $stageBefore = ReviewSchedule::normalizeStage($note->review_stage ?? 0);
            $stageAfter = ReviewSchedule::nextStage($stageBefore, $recalled);
            $dueAt = ReviewSchedule::dueDateFor($stageAfter, $note->discipline?->class_weekday, $today);
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
                return Outcome::success('Nota consolidada: ela sai da fila de revisão.');
            }

            return $recalled
                ? Outcome::success('Revisão registrada. Próxima cobrança agendada.')
                : Outcome::success('Sem problema: ela volta na próxima aula.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível registrar a revisão.');
        }
    }
}
