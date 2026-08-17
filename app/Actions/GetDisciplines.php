<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetDisciplines
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(): Outcome
    {
        try {
            $data = $this->appRepository->getDisciplines();

            return Outcome::noViewMessage($data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível pegar suas disciplinas!');
        }
    }
}
