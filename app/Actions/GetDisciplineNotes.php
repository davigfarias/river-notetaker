<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\NotesDTO;
use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetDisciplineNotes
{
    public function handle(int $disciplineId, int $accessTokenId): Outcome
    {
        try {
            $data = Notes::with(['concepts', 'pastoral_advice', 'references'])
                ->where('discipline_id', $disciplineId)
                ->where('access_token_id', $accessTokenId)
                ->get()
                ->map(fn (Notes $note): NotesDTO => NotesDTO::fromModel($note));

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar as notas da disciplina.');
        }
    }
}
