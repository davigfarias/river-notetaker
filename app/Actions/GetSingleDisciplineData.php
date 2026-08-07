<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetSingleDisciplineData
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    public function handle(string $slug): Outcome
    {
        try {
            $name = $this->appRepository->getDisciplineBySlug($slug);

            return Outcome::noViewMessage(data: $name);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível pegar o nome da disciplina!');
        }
    }
}
