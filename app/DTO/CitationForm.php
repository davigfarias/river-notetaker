<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Citation;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CitationForm extends Form
{
    #[Validate('required|string|min:3')]
    public string $quote_text = '';

    #[Validate('nullable|string|max:255')]
    public ?string $location = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $personal_note = null;

    public function fillFromModel(Citation $citation): void
    {
        $this->quote_text = $citation->quote_text;
        $this->location = $citation->location;
        $this->personal_note = $citation->personal_note;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'quote_text' => $this->quote_text,
            'location' => $this->location ?: null,
            'personal_note' => $this->personal_note ?: null,
        ];
    }
}
