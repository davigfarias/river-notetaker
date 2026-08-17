<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ListAccessTokens
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    public function handle(): Outcome
    {
        try {
            $data = $this->appRepository->listAccessTokens();

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível listar os tokens.');
        }
    }
}
