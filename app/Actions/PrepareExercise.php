<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PreparedExercise;
use App\Enums\ExerciseType;
use App\Support\Exercises\ExerciseStrategies;

final readonly class PrepareExercise
{
    public function __construct(
        private ExerciseStrategies $strategies,
        private LoadStudyPrinciple $load,
    ) {}

    public function handle(int $principleId, ExerciseType $type): PreparedExercise
    {
        return $this->strategies->for($type)->prepare($this->load->handle($principleId));
    }
}
