<?php

namespace Database\Factories;

use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Enums\ExportStatus;
use App\Models\Export;
use App\Models\ReferenceMaterial;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Export>
 */
#[UseModel(Export::class)]
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $format = $this->faker->randomElement(ExportFormat::cases());

        return [
            'access_token_id' => null,
            'format' => $format,
            'scope' => ExportScope::Reference,
            'reference_material_id' => ReferenceMaterial::factory(),
            'search_query' => null,
            'status' => ExportStatus::Pending,
            'disk' => null,
            'path' => null,
            'filename' => 'citacoes.'.$format->extension(),
            'file_size' => null,
            'error' => null,
            'expires_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ExportStatus::Completed,
            'disk' => (string) config('exports.disk', 'local'),
            'path' => 'exports/'.$this->faker->uuid().'.docx',
            'file_size' => $this->faker->numberBetween(1000, 50000),
            'expires_at' => now()->addDays(14),
        ]);
    }

    public function search(): static
    {
        return $this->state(fn (): array => [
            'scope' => ExportScope::Search,
            'reference_material_id' => null,
            'search_query' => $this->faker->word(),
        ]);
    }
}
