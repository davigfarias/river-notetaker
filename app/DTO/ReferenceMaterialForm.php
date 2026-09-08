<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
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

    #[Validate('nullable|string|max:2048')]
    public ?string $cover_path = null;

    // Book tracker fields — rules live entirely in rules() because they are
    // conditional; a #[Validate] attribute here would override the rules() entry
    // (Livewire merges attribute rules on top of the rules() method).
    public ?string $book_format = null;

    public ?int $page_count = null;

    public ?int $reader_start_page = null;

    public ?int $reader_end_page = null;

    public ?string $reading_status = null;

    public ?string $reading_started_at = null;

    public ?string $reading_finished_at = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::enum(ReferencesIcon::class)],
            'book_format' => ['nullable', Rule::enum(BookFormat::class)],
            'page_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'reader_start_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'reader_end_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'reading_status' => ['nullable', Rule::enum(ReadingStatus::class)],
            'reading_started_at' => ['nullable', 'date'],
            'reading_finished_at' => ['nullable', 'date'],
        ];

        if (! $this->isTrackableType()) {
            return $rules;
        }

        if ($this->isDigitalFormat()) {
            $rules['reader_start_page'] = ['required', 'integer', 'min:1', 'max:100000'];
            $rules['reader_end_page'] = ['required', 'integer', 'min:1', 'max:100000', 'gt:reader_start_page'];
        }

        if ($this->book_format === BookFormat::Physical->value) {
            $rules['page_count'] = ['required', 'integer', 'min:1', 'max:100000'];
        }

        if (filled($this->reading_started_at) && filled($this->reading_finished_at)) {
            $rules['reading_finished_at'] = ['nullable', 'date', 'after_or_equal:reading_started_at'];
        }

        return $rules;
    }

    public function fillFromModel(ReferenceMaterial $material): void
    {
        $this->title = $material->title;
        $this->author = $material->author;
        $this->year = $material->year;
        $this->type = $material->type;
        $this->publisher = $material->publisher;
        $this->url = $material->url;
        $this->cover_path = $material->cover_path;
        $this->abnt_reference = $material->abnt_reference;
        $this->book_format = $material->book_format?->value;
        $this->page_count = $material->page_count;
        $this->reader_start_page = $material->reader_start_page;
        $this->reader_end_page = $material->reader_end_page;
        $this->reading_status = $material->reading_status?->value;
        $this->reading_started_at = $material->reading_started_at?->toDateString();
        $this->reading_finished_at = $material->reading_finished_at?->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $attributes = [
            'title' => $this->title,
            'author' => $this->author ?: null,
            'year' => $this->year,
            'type' => $this->type,
            'publisher' => $this->publisher ?: null,
            'url' => $this->url ?: null,
            'cover_path' => $this->cover_path ?: null,
            'abnt_reference' => $this->abnt_reference ?: null,
        ];

        if (! $this->isTrackableType()) {
            return $attributes + [
                'book_format' => null,
                'page_count' => null,
                'reader_start_page' => null,
                'reader_end_page' => null,
                'reading_status' => null,
                'reading_started_at' => null,
                'reading_finished_at' => null,
            ];
        }

        $readerStart = $this->reader_start_page ?: null;
        $readerEnd = $this->reader_end_page ?: null;
        $pageCount = $this->page_count ?: null;

        if ($this->isDigitalFormat() && $readerStart !== null && $readerEnd !== null) {
            $pageCount = $readerEnd - $readerStart + 1;
        }

        return $attributes + [
            'book_format' => $this->book_format ?: null,
            'page_count' => $pageCount,
            'reader_start_page' => $this->isDigitalFormat() ? $readerStart : null,
            'reader_end_page' => $this->isDigitalFormat() ? $readerEnd : null,
            'reading_status' => $this->reading_status ?: null,
            'reading_started_at' => $this->reading_started_at ?: null,
            'reading_finished_at' => $this->reading_finished_at ?: null,
        ];
    }

    private function isDigitalFormat(): bool
    {
        return in_array($this->book_format, [
            BookFormat::Kindle->value,
            BookFormat::AppleBooks->value,
        ], true);
    }

    private function isTrackableType(): bool
    {
        return in_array($this->type, [
            ReferencesIcon::BookOpen->value,
            ReferencesIcon::Newspaper->value,
        ], true);
    }
}
