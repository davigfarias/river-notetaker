<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ReviewSchedule;
use App\ValueObjects\Date;
use Carbon\CarbonImmutable;
use Database\Factories\DisciplineFactory;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

/**
 * @property-read int $id
 * @property string $title
 * @property int $discipline_id
 * @property int|null $access_token_id
 * @property array<int, string>|null $tags
 * @property string|null $impressions
 * @property string|null $life_experiences
 * @property string|null $ai_summary
 * @property int $review_stage
 * @property CarbonImmutable|null $next_review_at
 * @property CarbonImmutable|null $consolidated_at
 */
#[UseFactory(NoteFactory::class)]
#[Fillable([
    'title',
    'tags',
    'discipline_id',
    'access_token_id',
    'impressions',
    'life_experiences',
    'ai_summary',
    'review_stage',
    'next_review_at',
    'consolidated_at',
    'updated_at',
])]
#[Table(name: 'notes')]
class Notes extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory, Searchable;

    public function casts(): array
    {
        return [
            'tags' => 'array',
            'ai_summary' => 'string',
            'review_stage' => 'integer',
            'next_review_at' => 'immutable_date',
            'consolidated_at' => 'immutable_datetime',
            'updated_at' => Date::class,
        ];
    }

    /**
     * @return BelongsTo<Disciplines, $this>
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Disciplines::class);
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return HasMany<Concepts, $this>
     */
    public function concepts(): HasMany
    {
        return $this->hasMany(Concepts::class, 'note_id');
    }

    /**
     * @return HasMany<PastoralAdvices, $this>
     */
    public function pastoral_advice(): HasMany
    {
        return $this->hasMany(PastoralAdvices::class, 'note_id');
    }

    /**
     * @return BelongsToMany<ReferenceMaterial, $this>
     */
    public function referenceMaterials(): BelongsToMany
    {
        return $this->belongsToMany(
            ReferenceMaterial::class,
            'note_reference_material',
            'note_id',
            'reference_material_id',
        );
    }

    /**
     * @return MorphMany<ReviewLog, $this>
     */
    public function reviewLogs(): MorphMany
    {
        return $this->morphMany(ReviewLog::class, 'reviewable');
    }

    /**
     * A nota está cobrando revisão na data informada.
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
     * @return array{
     *     title: string,
     *     tags: array<int, string>|null,
     *     discipline_id: int,
     *     impressions: string|null,
     *     life_experiences: string|null
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'tags' => $this->tags,
            'discipline_id' => $this->discipline_id,
            'impressions' => $this->impressions,
            'life_experiences' => $this->life_experiences,
        ];
    }
}
