<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Models\Disciplines;
use App\Models\Notes;
use App\Support\Outcome;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Traz para a fila as notas que ainda não tinham data de revisão, o que acontece
 * quando o dia de aula da disciplina é definido depois delas terem sido escritas.
 */
final readonly class ScheduleDisciplineReviews
{
    public function handle(Disciplines $discipline, ?CarbonImmutable $from = null): Outcome
    {
        try {
            if (! $discipline->isInReviewRotation()) {
                return Outcome::noViewMessage(data: 0);
            }

            $dueAt = ReviewSchedule::firstDueDate(
                $discipline->class_weekday,
                $from ?? CarbonImmutable::now(),
            );

            $scheduled = Notes::query()
                ->where('discipline_id', $discipline->id)
                ->whereNull('consolidated_at')
                ->whereNull('next_review_at')
                ->update([
                    'review_stage' => 1,
                    'next_review_at' => $dueAt?->toDateString(),
                ]);

            return Outcome::noViewMessage(data: $scheduled);
        } catch (\Exception $e) {
            Log::error("Erro ao agendar as revisões da disciplina: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível agendar as revisões desta disciplina.');
        }
    }
}
