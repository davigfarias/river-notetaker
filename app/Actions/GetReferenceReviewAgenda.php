<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DueReadingNoteReviewDTO;
use App\DTO\ReferenceReviewAgendaDTO;
use App\Support\Outcome;
use App\Support\ReviewSubject;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Monta a fila de revisão do dia de uma referência ou (com null) de todas as
 * referências do usuário. Livros revisam capítulos (só os que têm anotação);
 * artigos revisam anotações. Sem "dia de aula" para esperar: qualquer item
 * vencido hoje entra, respeitando um teto para a fila continuar terminável.
 * Itens dispensados ficam de fora.
 */
final readonly class GetReferenceReviewAgenda
{
    public const int DEFAULT_LIMIT = 8;

    public function handle(?int $referenceMaterialId, int $accessTokenId, ?CarbonImmutable $today = null, int $limit = self::DEFAULT_LIMIT): Outcome
    {
        try {
            $today = ($today ?? CarbonImmutable::now())->startOfDay();
            $kinds = [ReviewSubject::NOTE, ReviewSubject::CHAPTER];

            $owned = fn (string $kind): Builder => ReviewSubject::owned($kind, $accessTokenId)
                ->when($referenceMaterialId !== null, fn (Builder $query) => $query->where('reference_material_id', $referenceMaterialId))
                ->when($kind === ReviewSubject::CHAPTER, fn (Builder $query) => $query->whereNotNull('body')->where('body', '!=', ''));

            $dueQuery = fn (string $kind): Builder => $owned($kind)
                ->whereNull('consolidated_at')
                ->whereNull('dismissed_at')
                ->whereNotNull('next_review_at')
                ->whereDate('next_review_at', '<=', $today->toDateString());

            $totalDue = 0;
            $due = collect();

            foreach ($kinds as $kind) {
                $totalDue += $dueQuery($kind)->count();

                $due = $due->concat(
                    $dueQuery($kind)
                        ->with('referenceMaterial:id,title')
                        ->orderBy('next_review_at')
                        ->orderBy('id')
                        ->limit($limit)
                        ->get()
                        ->map(fn (Model $item): DueReadingNoteReviewDTO => DueReadingNoteReviewDTO::fromModel($item, $today, $kind))
                );
            }

            $due = $due
                ->sortBy([['due_at', 'asc'], ['id', 'asc']])
                ->take($limit)
                ->values();

            $nextDueAt = collect($kinds)
                ->map(fn (string $kind) => $owned($kind)
                    ->whereNull('consolidated_at')
                    ->whereNull('dismissed_at')
                    ->whereDate('next_review_at', '>', $today->toDateString())
                    ->min('next_review_at'))
                ->filter()
                ->min();

            return Outcome::noViewMessage(new ReferenceReviewAgendaDTO(
                due: $due,
                totalDue: $totalDue,
                totalNotes: collect($kinds)->sum(fn (string $kind): int => $owned($kind)->count()),
                dismissedCount: collect($kinds)->sum(fn (string $kind): int => $owned($kind)->whereNotNull('dismissed_at')->count()),
                nextDueAt: $nextDueAt !== null ? CarbonImmutable::parse($nextDueAt)->toDateString() : null,
            ));
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível montar a fila de revisão das anotações.');
        }
    }
}
