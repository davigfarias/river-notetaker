<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RevokeAccessToken
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    public function handle(int $id): Outcome
    {
        try {
            $revoked = $this->appRepository->revokeAccessToken($id);

            if (! $revoked) {
                return Outcome::failure(message: 'Token não encontrado ou já revogado.');
            }

            return Outcome::success(message: 'Token revogado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível revogar o token.');
        }
    }
}
