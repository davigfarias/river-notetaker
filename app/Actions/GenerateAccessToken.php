<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GenerateAccessToken
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    public function handle(string $name): Outcome
    {
        try {
            $result = $this->appRepository->createAccessToken($name);

            return Outcome::success(message: 'Token gerado com sucesso.', data: $result);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível gerar o token.');
        }
    }
}
