<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repository\AppRepository;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SearchConcept
{
    public function __construct(
        private AppRepository $appRepository,
    ) {}

    public function handle(string $search): Outcome
    {
        try {
            $data = $this->appRepository
                ->searchConcepts($search);

            return Outcome::success(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
