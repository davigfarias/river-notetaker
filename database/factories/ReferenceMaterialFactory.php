<?php

namespace Database\Factories;

use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
use App\Enums\ReferencesIcon;
use App\Models\ReferenceMaterial;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceMaterial>
 */
#[UseModel(ReferenceMaterial::class)]
class ReferenceMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $author = $this->faker->name();
        $title = rtrim($this->faker->sentence(4), '.');
        $year = $this->faker->numberBetween(1950, 2025);

        return [
            'access_token_id' => null,
            'title' => $title,
            'author' => $author,
            'year' => $year,
            'type' => $this->faker->randomElement(ReferencesIcon::options()),
            'publisher' => $this->faker->company(),
            'url' => null,
            'abnt_reference' => sprintf(
                '%s. %s. %s: %s, %d.',
                mb_strtoupper((string) $this->lastName($author)),
                $title,
                $this->faker->city(),
                $this->faker->company(),
                $year,
            ),
        ];
    }

    public function book(): static
    {
        return $this->state(fn (): array => ['type' => ReferencesIcon::BookOpen->value]);
    }

    public function article(): static
    {
        return $this->state(fn (): array => ['type' => ReferencesIcon::Newspaper->value]);
    }

    public function tracking(int $total = 200, int $read = 50): static
    {
        return $this->state(fn (): array => [
            'type' => ReferencesIcon::BookOpen->value,
            'book_format' => BookFormat::Physical->value,
            'page_count' => $total,
            'current_page' => $read,
            'reading_status' => ReadingStatus::Reading->value,
            'reading_started_at' => now()->subDays(7)->toDateString(),
        ]);
    }

    public function digital(int $start, int $end): static
    {
        return $this->state(fn (): array => [
            'type' => ReferencesIcon::BookOpen->value,
            'book_format' => BookFormat::Kindle->value,
            'reader_start_page' => $start,
            'reader_end_page' => $end,
            'page_count' => $end - $start + 1,
            'current_page' => $start,
            'reading_status' => ReadingStatus::WantToRead->value,
        ]);
    }

    private function lastName(string $author): string
    {
        $parts = preg_split('/\s+/', trim($author)) ?: [$author];

        return end($parts);
    }
}
