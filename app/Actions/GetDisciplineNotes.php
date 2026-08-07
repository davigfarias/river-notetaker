<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetDisciplineNotes
{
    public function __construct(
        private AppRepository $appRepository) {}

    public function handle(int $disciplineId): Outcome
    {
        try {
            $data = $this->appRepository->getNotesByDisciplineId($disciplineId);

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar as notas da disciplina.');
        }
    }
}
