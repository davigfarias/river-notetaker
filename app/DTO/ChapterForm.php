<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Chapter;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ChapterForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    public function fillFromChapter(Chapter $chapter): void
    {
        $this->title = $chapter->title;
    }

    public function toData(): ChapterData
    {
        return new ChapterData(
            title: $this->title,
        );
    }
}
