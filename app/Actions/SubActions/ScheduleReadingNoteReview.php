<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Models\ReadingNote;
use App\Support\Outcome;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Coloca uma anotação de leitura no primeiro degrau da escada, cobrando
 * amanhã. Sem dia de aula para ancorar, o ciclo conta em dias corridos.
 */
final readonly class ScheduleReadingNoteReview
{
    public function handle(ReadingNote $note, ?CarbonImmutable $from = null): Outcome
    {
        try {
            $dueAt = ReviewSchedule::firstDueDateDaily($from ?? CarbonImmutable::now());

            $note->forceFill([
                'review_stage' => 1,
                'next_review_at' => $dueAt?->toDateString(),
                'consolidated_at' => null,
            ])->saveQuietly();

            return Outcome::noViewMessage(data: $note);
        } catch (\Exception $e) {
            Log::error("Erro ao agendar a revisão da anotação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível agendar a revisão desta anotação.');
        }
    }
}
