<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Disciplines;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ToggleDisciplineTopic
{
    public function handle(int $disciplineId, int $topicId): Outcome
    {
        try {
            $discipline = Disciplines::findOrFail($disciplineId);

            $discipline->principleTopics()->toggle($topicId);

            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Erro ao vincular tema à disciplina: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar os temas da disciplina.');
        }
    }
}
