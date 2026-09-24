<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Database\Factories\ReadingNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $reference_material_id
 * @property int|null $access_token_id
 * @property string|null $title
 * @property string $body
 * @property string|null $location
 * @property array<int, string>|null $tags
 * @property int|null $page_snapshot
 * @property int $review_stage
 * @property CarbonImmutable|null $next_review_at
 * @property CarbonImmutable|null $consolidated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(ReadingNoteFactory::class)]
#[Fillable([
    'reference_material_id',
    'access_token_id',
    'title',
    'body',
    'location',
    'tags',
    'page_snapshot',
])]
#[Table(name: 'reading_notes')]
class ReadingNote extends Model
{
    /** @use HasFactory<ReadingNoteFactory> */
    use HasFactory, Searchable;

    public function casts(): array
    {
        return [
            'tags' => 'array',
            'page_snapshot' => 'integer',
            'review_stage' => 'integer',
            'next_review_at' => 'immutable_date',
            'consolidated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<ReferenceMaterial, $this>
     */
    public function referenceMaterial(): BelongsTo
    {
        return $this->belongsTo(ReferenceMaterial::class);
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return MorphMany<ReviewLog, $this>
     */
    public function reviewLogs(): MorphMany
    {
        return $this->morphMany(ReviewLog::class, 'reviewable');
    }

    /**
     * A anotação está cobrando revisão na data informada.
     */
    public function isDueForReview(CarbonImmutable $on): bool
    {
        return ! $this->isConsolidated()
            && $this->next_review_at instanceof CarbonImmutable
            && $this->next_review_at->lessThanOrEqualTo($on->startOfDay());
    }

    public function isConsolidated(): bool
    {
        return $this->consolidated_at instanceof CarbonImmutable
            || ReviewSchedule::isConsolidated($this->review_stage ?? 0);
    }

    /**
     * The heading shown in listings: the title when given, otherwise the opening words of the body.
     */
    public function displayTitle(): string
    {
        return filled($this->title)
            ? (string) $this->title
            : Str::limit(Str::squish($this->body), 60);
    }

    /**
     * @return array{
     *     title: string|null,
     *     body: string,
     *     location: string|null,
     *     tags: array<int, string>|null
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'location' => $this->location,
            'tags' => $this->tags,
        ];
    }
}
