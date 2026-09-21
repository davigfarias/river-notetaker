<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DueReviewDTO;
use App\DTO\ReviewAgendaDTO;
use App\DTO\UpcomingLessonDTO;
use App\Enums\Weekday;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Monta a fila de revisão do dia. Só cobra notas de disciplinas que têm aula
 * hoje e que não foram encerradas, priorizando as mais atrasadas e respeitando
 * um teto para a fila continuar terminável.
 */
final readonly class GetReviewAgenda
{
    public const int DEFAULT_LIMIT = 8;

    public function handle(int $accessTokenId, ?CarbonImmutable $today = null, int $limit = self::DEFAULT_LIMIT): Outcome
    {
        try {
            $today = ($today ?? CarbonImmutable::now())->startOfDay();
            $weekday = Weekday::fromDate($today);

            $disciplinesToday = Disciplines::query()
                ->whereNull('completed_at')
                ->where('class_weekday', $weekday->value)
                ->pluck('id');

            $dueQuery = Notes::query()
                ->with('discipline')
                ->summarized()
                ->where('access_token_id', $accessTokenId)
                ->whereNull('consolidated_at')
                ->whereNotNull('next_review_at')
                ->whereDate('next_review_at', '<=', $today->toDateString())
                ->whereIn('discipline_id', $disciplinesToday);

            $totalDue = (clone $dueQuery)->count();

            $due = $dueQuery
                ->orderBy('next_review_at')
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map(fn (Notes $note): DueReviewDTO => DueReviewDTO::fromModel($note, $today));

            return Outcome::noViewMessage(new ReviewAgendaDTO(
                today: $weekday,
                due: $due,
                totalDue: $totalDue,
                upcoming: $this->upcomingLessons($accessTokenId, $today),
                hasLessonToday: $disciplinesToday->isNotEmpty(),
                awaitingSummaryCount: $this->awaitingSummaryCount($accessTokenId),
            ));
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível montar a fila de revisão de hoje.');
        }
    }

    /**
     * Quantas notas ainda devem o resumo escrito à mão. Enquanto ele não vier,
     * elas ficam fora da fila: não há texto para apagar em lacunas.
     */
    private function awaitingSummaryCount(int $accessTokenId): int
    {
        $disciplinesInRotation = Disciplines::query()
            ->whereNull('completed_at')
            ->whereNotNull('class_weekday')
            ->pluck('id');

        return (int) Notes::query()
            ->awaitingSummary()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('consolidated_at')
            ->whereIn('discipline_id', $disciplinesInRotation)
            ->count();
    }

    /**
     * Próximos encontros de cada disciplina ativa, com quantas notas já estarão
     * cobrando revisão lá. Serve para a tela não ficar vazia fora do dia de aula.
     *
     * @return Collection<int, UpcomingLessonDTO>
     */
    private function upcomingLessons(int $accessTokenId, CarbonImmutable $today): Collection
    {
        return Disciplines::query()
            ->whereNull('completed_at')
            ->whereNotNull('class_weekday')
            ->orderBy('title')
            ->get()
            ->map(function (Disciplines $discipline) use ($accessTokenId, $today): UpcomingLessonDTO {
                $weekday = $discipline->class_weekday;
                $nextLesson = $weekday->onOrAfter($today->addDay());

                return new UpcomingLessonDTO(
                    disciplineTitle: $discipline->title,
                    weekdayLabel: $weekday->label(),
                    date: $nextLesson->toDateString(),
                    dueCount: (int) Notes::query()
                        ->summarized()
                        ->where('access_token_id', $accessTokenId)
                        ->where('discipline_id', $discipline->id)
                        ->whereNull('consolidated_at')
                        ->whereNotNull('next_review_at')
                        ->whereDate('next_review_at', '<=', $nextLesson->toDateString())
                        ->count(),
                );
            })
            ->sortBy(fn (UpcomingLessonDTO $lesson): string => $lesson->date)
            ->values();
    }
}
