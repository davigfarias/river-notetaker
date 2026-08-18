<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\NotesDTO;
use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CreateNote
{
    public function handle(NotesDTO $data): Outcome
    {
        try {
            $note = Notes::create([
                'discipline_id' => $data->discipline_id,
                'title' => $data->title,
                'tags' => $data->tags,
                'impressions' => $data->impressions,
                'life_experiences' => $data->life_experiences,
                'access_token_id' => $data->access_token_id,
            ]);

            return Outcome::noViewMessage(data: $note);

        } catch (\Exception $e) {
            Log::error("Erro na criação da nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Falha ao salvar os dados principais da nota.');
        }
    }
}
