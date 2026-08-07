<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetConceptsByLetter
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(string $letter): Outcome
    {
        try {
            $data = $this->appRepository
                ->getConceptsByLetter($letter);

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar conceitos por letra: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os conceitos desta letra.');
        }
    }
}
