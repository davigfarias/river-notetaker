<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleTopic;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetPrincipleTopics
{
    public function handle(): Outcome
    {
        try {
            $data = PrincipleTopic::withCount('principles')
                ->orderBy('title')
                ->get();

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar temas: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os temas.', data: collect());
        }
    }
}
