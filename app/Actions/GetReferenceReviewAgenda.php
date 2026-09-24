<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DueReadingNoteReviewDTO;
use App\DTO\ReferenceReviewAgendaDTO;
use App\Models\ReadingNote;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Monta a fila de revisão do dia das anotações de leitura de uma referência.
 * Sem "dia de aula" para esperar: qualquer anotação vencida hoje entra,
 * respeitando um teto para a fila continuar terminável.
 */
final readonly class GetReferenceReviewAgenda
{
    public const int DEFAULT_LIMIT = 8;

    public function handle(int $referenceMaterialId, int $accessTokenId, ?CarbonImmutable $today = null, int $limit = self::DEFAULT_LIMIT): Outcome
    {
        try {
            $today = ($today ?? CarbonImmutable::now())->startOfDay();

            $dueQuery = ReadingNote::query()
                ->where('reference_material_id', $referenceMaterialId)
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->whereNull('consolidated_at')
                ->whereNotNull('next_review_at')
                ->whereDate('next_review_at', '<=', $today->toDateString());

            $totalDue = (clone $dueQuery)->count();

            $due = $dueQuery
                ->orderBy('next_review_at')
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map(fn (ReadingNote $note): DueReadingNoteReviewDTO => DueReadingNoteReviewDTO::fromModel($note, $today));

            return Outcome::noViewMessage(new ReferenceReviewAgendaDTO(
                due: $due,
                totalDue: $totalDue,
            ));
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível montar a fila de revisão desta referência.');
        }
    }
}
