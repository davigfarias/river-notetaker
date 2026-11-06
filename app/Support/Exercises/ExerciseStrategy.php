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

    public function prepare(StudyPrinciple $principle): PreparedExercise;
}
