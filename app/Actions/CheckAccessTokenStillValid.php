<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CheckAccessTokenStillValid
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    public function handle(int $id): Outcome
    {
        try {
            if (! $this->appRepository->isAccessTokenActive($id)) {
                return Outcome::failure(message: 'Token inválido ou revogado.');
            }

            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível validar o token.');
        }
    }
}
