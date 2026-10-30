<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingNote;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Tira a anotação da fila de revisão sem apagá-la nem consolidá-la. O estágio
 * e o histórico ficam intactos para ela poder voltar depois.
 */
final readonly class DismissReadingNoteReview
{
    public function handle(int $readingNoteId, int $accessTokenId): Outcome
    {
        try {
            $note = ReadingNote::query()
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->find($readingNoteId);

            if (! $note instanceof ReadingNote) {
                return Outcome::failure('Anotação não encontrada.');
            }

            $note->forceFill(['dismissed_at' => CarbonImmutable::now()])->saveQuietly();

            return Outcome::success('Anotação tirada da fila. Dá para desfazer em "Tiradas da fila".');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível tirar a anotação da fila.');
        }
    }
}
