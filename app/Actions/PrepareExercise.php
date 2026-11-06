<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;
use App\Models\Principle;
use App\Support\Exercises\ExerciseStrategies;

final readonly class PrepareExercise
{
    public function __construct(private ExerciseStrategies $strategies) {}

    public function handle(int $principleId, ExerciseType $type): PreparedExercise
    {
        $principle = Principle::query()->with('concept')->findOrFail($principleId);

        return $this->strategies->for($type)->prepare(StudyPrinciple::from($principle));
    }
}
