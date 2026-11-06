<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;

interface ExerciseStrategy
{
    public function type(): ExerciseType;

    /** A resposta livre deste tipo é avaliada pela IA (quando disponível). */
    public function evaluatesWithAi(): bool;

    /**
     * @param  array<string, mixed>|null  $payload  conteúdo gerado pela IA (tipos que exigem geração)
     */
    public function prepare(StudyPrinciple $principle, ?array $payload = null): PreparedExercise;
}
