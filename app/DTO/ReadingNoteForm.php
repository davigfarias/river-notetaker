<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\ReadingNote;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ReadingNoteForm extends Form
{
    #[Validate('nullable|string|max:255')]
    public ?string $title = null;

    #[Validate('required|string|min:3')]
    public string $body = '';

    #[Validate('nullable|string|max:255')]
    public ?string $location = null;

    /** @var array<int, string> */
    #[Validate('nullable|array')]
    public array $tags = [];

    public function fillFromModel(ReadingNote $note): void
    {
        $this->title = $note->title;
        $this->body = $note->body;
        $this->location = $note->location;
        $this->tags = $note->tags ?? [];
    }

    public function toggleTag(string $title): void
    {
        $this->tags = in_array($title, $this->tags, true)
            ? array_values(array_filter($this->tags, fn (string $tag): bool => $tag !== $title))
            : [...$this->tags, $title];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title ?: null,
            'body' => $this->body,
            'location' => $this->location ?: null,
            'tags' => $this->tags === [] ? null : array_values($this->tags),
        ];
    }
}
