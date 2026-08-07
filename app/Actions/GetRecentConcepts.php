<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetRecentConcepts
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(int $limit = 3): Outcome
    {
        try {
            $data = $this->appRepository
                ->getRecentConcepts($limit);

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar conceitos recentes: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os conceitos recentes.');
        }
    }
}
