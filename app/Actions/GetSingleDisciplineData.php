<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DisciplinesDTO;
use App\Models\Disciplines;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetSingleDisciplineData
{
    public function handle(string $slug): Outcome
    {
        try {
            $name = Disciplines::search($slug)
                ->get()
                ->map(fn (Disciplines $discipline): DisciplinesDTO => new DisciplinesDTO(
                    id: $discipline->id,
                    title: $discipline->title,
                    icon: $discipline->icon,
                ))
                ->first();

            return Outcome::noViewMessage(data: $name);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível pegar o nome da disciplina!');
        }
    }
}
