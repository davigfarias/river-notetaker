<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use App\Support\ReviewSubject;
use Illuminate\Support\Facades\Log;

final readonly class GetReadingNote
{
    public function handle(int $id, int $accessTokenId, string $kind = ReviewSubject::NOTE): Outcome
    {
        try {
            $note = ReviewSubject::owned($kind, $accessTokenId)->find($id);

            return Outcome::noViewMessage(data: $note);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar a anotação de leitura: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar a anotação.');
        }
    }
}
