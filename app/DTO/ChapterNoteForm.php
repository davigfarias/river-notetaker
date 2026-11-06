<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Chapter;
use Livewire\Attributes\Validate;
use Livewire\Form;

/**
 * Anotação de um capítulo de livro. O texto é opcional: capítulo sem texto
 * existe (planejamento de leitura), mas não entra na revisão.
 */
class ChapterNoteForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|min:3|max:100000')]
    public ?string $body = null;

    #[Validate('nullable|string|max:255')]
    public ?string $location = null;

    /** @var array<int, string> */
    #[Validate('nullable|array')]
    public array $tags = [];

    public function fillFromChapter(Chapter $chapter): void
    {
        $this->title = $chapter->title;
        $this->body = $chapter->body;
        $this->location = $chapter->location;
        $this->tags = $chapter->tags ?? [];
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
            'title' => $this->title,
            'body' => filled($this->body) ? $this->body : null,
            'location' => $this->location ?: null,
            'tags' => $this->tags === [] ? null : array_values($this->tags),
        ];
    }
}
