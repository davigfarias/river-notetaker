<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\Era;
use App\Enums\HistoricalEventNature;
use App\Models\HistoricalEvent;
use App\ValueObjects\HistoricalYear;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class HistoricalEventForm extends Form
{
    #[Validate('required|string|min:2|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public ?string $description = null;

    // Enum and range rules live entirely in rules(): a #[Validate] attribute on
    // the same key would override the rules() entry.
    public string $nature = 'event';

    public ?int $start_year = null;

    public string $start_era = 'ad';

    public ?int $end_year = null;

    public string $end_era = 'ad';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nature' => ['required', Rule::enum(HistoricalEventNature::class)],
            'start_year' => ['required', 'integer', 'min:1', 'max:9999'],
            'start_era' => ['required', Rule::enum(Era::class)],
            'end_year' => ['nullable', 'integer', 'min:1', 'max:9999', $this->endNotBeforeStart(...)],
            'end_era' => ['required_with:end_year', Rule::enum(Era::class)],
        ];
    }

    public function fillFromModel(HistoricalEvent $event): void
    {
        $this->title = $event->title;
        $this->description = $event->description;
        $this->nature = $event->nature->value;
        $this->start_year = $event->start_year;
        $this->start_era = $event->start_era->value;
        $this->end_year = $event->end_year;
        $this->end_era = $event->end_era->value ?? Era::AnnoDomini->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $hasEnd = $this->end_year !== null;

        return [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'nature' => $this->nature,
            'start_year' => $this->start_year,
            'start_era' => $this->start_era,
            'end_year' => $hasEnd ? $this->end_year : null,
            'end_era' => $hasEnd ? $this->end_era : null,
        ];
    }

    private function endNotBeforeStart(string $attribute, mixed $value, Closure $fail): void
    {
        $startEra = Era::tryFrom($this->start_era);
        $endEra = Era::tryFrom($this->end_era);

        if ($this->start_year === null || $this->start_year < 1 || $startEra === null || $endEra === null || (int) $value < 1) {
            return;
        }

        $start = new HistoricalYear($this->start_year, $startEra);
        $end = new HistoricalYear((int) $value, $endEra);

        if ($end->sortKey() < $start->sortKey()) {
            $fail('O ano de fim não pode ser anterior ao ano de início.');
        }
    }
}
