<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ValidateAccessToken
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    public function handle(string $plainText): Outcome
    {
        try {
            $token = $this->appRepository->findValidAccessTokenByPlainText($plainText);

            if (! $token) {
                return Outcome::failure(message: 'Código inválido.');
            }

            $this->appRepository->touchAccessTokenLastUsed((int) $token->id);

            return Outcome::success(data: $token);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível validar o token.');
        }
    }
}
