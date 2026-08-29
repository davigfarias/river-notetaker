<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\ReferencesIcon;
use App\Models\ReferenceMaterial;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ReferenceMaterialForm extends Form
{
    #[Validate('required|string|min:2|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public ?string $author = null;

    #[Validate('nullable|integer|min:0|max:2100')]
    public ?int $year = null;

    #[Validate('required|string')]
    public string $type = ReferencesIcon::BookOpen->value;

    #[Validate('nullable|string|max:255')]
    public ?string $publisher = null;

    #[Validate('nullable|url|max:2048')]
    public ?string $url = null;

    #[Validate('nullable|string|max:2000')]
    public ?string $abnt_reference = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReferencesIcon::class)],
        ];
    }

    public function fillFromModel(ReferenceMaterial $material): void
    {
        $this->title = $material->title;
        $this->author = $material->author;
        $this->year = $material->year;
        $this->type = $material->type;
        $this->publisher = $material->publisher;
        $this->url = $material->url;
        $this->abnt_reference = $material->abnt_reference;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'author' => $this->author ?: null,
            'year' => $this->year,
            'type' => $this->type,
            'publisher' => $this->publisher ?: null,
            'url' => $this->url ?: null,
            'abnt_reference' => $this->abnt_reference ?: null,
        ];
    }
}
