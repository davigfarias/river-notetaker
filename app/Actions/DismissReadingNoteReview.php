<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\ReviewSubject;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Tira a anotação da fila de revisão sem apagá-la nem consolidá-la. O estágio
 * e o histórico ficam intactos para ela poder voltar depois.
 */
final readonly class DismissReadingNoteReview
{
    public function handle(int $readingNoteId, int $accessTokenId, string $kind = ReviewSubject::NOTE): Outcome
    {
        try {
            $note = ReviewSubject::owned($kind, $accessTokenId)->find($readingNoteId);

            if (! $note instanceof Model) {
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
