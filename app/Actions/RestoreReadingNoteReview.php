<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingNote;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Devolve à fila uma anotação dispensada: ela volta a cobrar hoje, no mesmo
 * degrau em que estava.
 */
final readonly class RestoreReadingNoteReview
{
    public function handle(int $readingNoteId, int $accessTokenId, ?CarbonImmutable $today = null): Outcome
    {
        try {
            $today = ($today ?? CarbonImmutable::now())->startOfDay();

            $note = ReadingNote::query()
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->find($readingNoteId);

            if (! $note instanceof ReadingNote) {
                return Outcome::failure('Anotação não encontrada.');
            }

            $note->forceFill([
                'dismissed_at' => null,
                'next_review_at' => $note->isConsolidated() ? $note->next_review_at : $today->toDateString(),
            ])->saveQuietly();

            return Outcome::success('Anotação de volta na fila de revisão.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível devolver a anotação à fila.');
        }
    }
}
