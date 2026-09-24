<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReadingNote;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetReadingNote
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            $note = ReadingNote::query()
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->find($id);

            return Outcome::noViewMessage(data: $note);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar a anotação de leitura: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar a anotação.');
        }
    }
}
