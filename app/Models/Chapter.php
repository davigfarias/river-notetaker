<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;
use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $reference_material_id
 * @property string $title
 * @property int $position
 * @property string|null $body
 * @property string|null $location
 * @property array<int, string>|null $tags
 * @property int|null $page_snapshot
 * @property int $review_stage
 * @property CarbonImmutable|null $next_review_at
 * @property CarbonImmutable|null $consolidated_at
 * @property CarbonImmutable|null $dismissed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ChapterFactory::class)]
#[Fillable([
    'reference_material_id',
    'title',
    'position',
    'body',
    'location',
    'tags',
    'page_snapshot',
])]
#[Table(name: 'chapters')]
class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory, Searchable;

    public function casts(): array
    {
        return [
            'position' => 'integer',
            'tags' => 'array',
            'page_snapshot' => 'integer',
            'review_stage' => 'integer',
            'next_review_at' => 'immutable_date',
            'consolidated_at' => 'immutable_datetime',
            'dismissed_at' => 'immutable_datetime',
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
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    /**
     * @return MorphMany<ReviewLog, $this>
     */
    public function reviewLogs(): MorphMany
    {
        return $this->morphMany(ReviewLog::class, 'reviewable');
    }

    /**
     * Só há o que revisar (e do que gerar MCQ) quando o capítulo tem texto.
     */
    public function hasNote(): bool
    {
        return filled($this->body);
    }

    public function isDueForReview(CarbonImmutable $on): bool
    {
        return $this->hasNote()
            && ! $this->isConsolidated()
            && $this->dismissed_at === null
            && $this->next_review_at instanceof CarbonImmutable
            && $this->next_review_at->lessThanOrEqualTo($on->startOfDay());
    }

    public function isConsolidated(): bool
    {
        return $this->consolidated_at instanceof CarbonImmutable
            || ReviewSchedule::isConsolidated($this->review_stage ?? 0);
    }

    public function displayTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array{title: string, body: string|null, location: string|null, tags: array<int, string>|null}
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
