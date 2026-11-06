<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\StudyPrinciple;
use App\Models\Principle;

final readonly class LoadStudyPrinciple
{
    public function handle(int $principleId): StudyPrinciple
    {
        return StudyPrinciple::from(Principle::query()->with(['concept', 'category'])->findOrFail($principleId));
    }
}
