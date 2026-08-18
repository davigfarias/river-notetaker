<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\NotesDTO;
use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetNote
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            $note = Notes::with(['concepts', 'pastoral_advice', 'references'])
                ->where('access_token_id', $accessTokenId)
                ->find($id);

            $data = $note ? NotesDTO::fromModel($note) : null;

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar a nota selecionada.');
        }
    }
}
