<?php

namespace Database\Factories;

use App\Models\Planner;
use App\Models\PlannerItem;
use App\Models\ReferenceMaterial;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannerItem>
 */
#[UseModel(PlannerItem::class)]
class PlannerItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planner_id' => Planner::factory(),
            'reference_material_id' => ReferenceMaterial::factory(),
            'chapter_id' => null,
            'position' => 0,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}
