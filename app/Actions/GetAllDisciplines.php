<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DisciplinesDTO;
use App\Models\Disciplines;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetAllDisciplines
{
    public function handle(): Outcome
    {
        try {
            $data = Disciplines::all()
                ->map(fn (Disciplines $discipline): DisciplinesDTO => DisciplinesDTO::fromModel($discipline));

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível pegar as disciplinas disponíveis');
        }
    }
}
