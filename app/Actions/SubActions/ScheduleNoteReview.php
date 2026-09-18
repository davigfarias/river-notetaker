<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Models\Notes;
use App\Support\Outcome;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Coloca uma nota no primeiro degrau da escada, cobrando na próxima aula da
 * disciplina. Sem dia de aula definido, a nota fica fora da fila.
 */
final readonly class ScheduleNoteReview
{
    public function handle(Notes $note, ?CarbonImmutable $from = null): Outcome
    {
        try {
            $note->loadMissing('discipline');

            $dueAt = ReviewSchedule::firstDueDate(
                $note->discipline?->class_weekday,
                $from ?? CarbonImmutable::now(),
            );

            $note->forceFill([
                'review_stage' => 1,
                'next_review_at' => $dueAt?->toDateString(),
                'consolidated_at' => null,
            ])->saveQuietly();

            return Outcome::noViewMessage(data: $note);
        } catch (\Exception $e) {
            Log::error("Erro ao agendar a revisão da nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível agendar a revisão desta nota.');
        }
    }
}
