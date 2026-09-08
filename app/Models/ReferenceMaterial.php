<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
use App\Enums\ReferencesIcon;
use Carbon\CarbonImmutable;
use Database\Factories\ReferenceMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int|null $access_token_id
 * @property string $title
 * @property string|null $author
 * @property int|null $year
 * @property string $type
 * @property string|null $publisher
 * @property string|null $url
 * @property string|null $cover_path
 * @property string|null $abnt_reference
 * @property BookFormat|null $book_format
 * @property int|null $page_count
 * @property int|null $current_page
 * @property int|null $reader_start_page
 * @property int|null $reader_end_page
 * @property ReadingStatus|null $reading_status
 * @property CarbonImmutable|null $reading_started_at
 * @property CarbonImmutable|null $reading_finished_at
 */
#[UseFactory(ReferenceMaterialFactory::class)]
#[Fillable([
    'access_token_id',
    'title',
    'author',
    'year',
    'type',
    'publisher',
    'url',
    'cover_path',
    'abnt_reference',
    'book_format',
    'page_count',
    'current_page',
    'reader_start_page',
    'reader_end_page',
    'reading_status',
    'reading_started_at',
    'reading_finished_at',
])]
#[Table(name: 'reference_materials')]
class ReferenceMaterial extends Model
{
    /** @use HasFactory<ReferenceMaterialFactory> */
    use HasFactory, Searchable;

    public function casts(): array
    {
        return [
            'year' => 'integer',
            'page_count' => 'integer',
            'current_page' => 'integer',
            'reader_start_page' => 'integer',
            'reader_end_page' => 'integer',
            'book_format' => BookFormat::class,
            'reading_status' => ReadingStatus::class,
            'reading_started_at' => 'date',
            'reading_finished_at' => 'date',
        ];
    }

    /**
     * Whether this material is a book or article and can be tracked as a read.
     */
    public function isTrackable(): bool
    {
        return in_array($this->type, [
            ReferencesIcon::BookOpen->value,
            ReferencesIcon::Newspaper->value,
        ], true);
    }

    /**
     * Whether the tracker has enough data to show a page-based progress bar.
     */
    public function hasReadingProgress(): bool
    {
        return $this->isTrackable() && $this->page_count !== null;
    }

    /**
     * The page numbering the reader/slider operates in.
     *
     * @return array{start: int, end: int}
     */
    public function readingRange(): array
    {
        if ($this->book_format?->isDigital() && $this->reader_start_page !== null && $this->reader_end_page !== null) {
            return ['start' => $this->reader_start_page, 'end' => $this->reader_end_page];
        }

        return ['start' => 1, 'end' => (int) ($this->page_count ?? 1)];
    }

    public function pagesTotal(): ?int
    {
        if ($this->book_format?->isDigital() && $this->reader_start_page !== null && $this->reader_end_page !== null) {
            return $this->reader_end_page - $this->reader_start_page + 1;
        }

        return $this->page_count;
    }

    public function pagesRead(): int
    {
        $total = $this->pagesTotal();

        if ($total === null || $this->current_page === null) {
            return 0;
        }

        if ($this->book_format?->isDigital() && $this->reader_start_page !== null) {
            $read = $this->current_page - $this->reader_start_page + 1;
        } else {
            $read = $this->current_page;
        }

        return max(0, min($read, $total));
    }

    public function readingProgressPercent(): int
    {
        $total = $this->pagesTotal();

        if ($total === null || $total <= 0) {
            return 0;
        }

        return max(0, min(100, (int) round($this->pagesRead() / $total * 100)));
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return HasMany<Citation, $this>
     */
    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    /**
     * @return HasMany<Chapter, $this>
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('position');
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_path === null) {
            return null;
        }

        return Storage::url($this->cover_path);
    }

    /**
     * @return BelongsToMany<Notes, $this>
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(
            Notes::class,
            'note_reference_material',
            'reference_material_id',
            'note_id',
        );
    }

    public function typeIcon(): ReferencesIcon
    {
        return ReferencesIcon::tryFrom($this->type) ?? ReferencesIcon::BookOpen;
    }

    /**
     * @return array{
     *     title: string,
     *     author: string|null,
     *     abnt_reference: string|null
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'author' => $this->author,
            'abnt_reference' => $this->abnt_reference,
        ];
    }
}
