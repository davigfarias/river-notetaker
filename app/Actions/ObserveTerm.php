<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class ObserveTerm
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(string $term): Outcome
    {
        try {
            $check = $this->appRepository
                ->checkTermExistence(Str::lower($term));

            return Outcome::success(data: $check);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
