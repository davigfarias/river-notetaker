<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\NotesDTO;
use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CreateNote
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(NotesDTO $data): Outcome
    {
        try {
            $note = $this->appRepository->saveNote($data);

            return Outcome::noViewMessage(data: $note);

        } catch (\Exception $e) {
            Log::error("Erro na criação da nota: {$e->getMessage()}");

            return Outcome::failure(message: 'Falha ao salvar os dados principais da nota.');
        }
    }
}
