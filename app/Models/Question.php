<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $chapter_id
 * @property string $prompt
 * @property string $reference_answer
 * @property string|null $keywords
 * @property bool $is_cloze
 * @property array<int, int>|null $cloze_blank_indices
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(QuestionFactory::class)]
#[Fillable([
    'chapter_id',
    'prompt',
    'reference_answer',
    'keywords',
    'is_cloze',
    'cloze_blank_indices',
    'position',
])]
#[Table(name: 'questions')]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'is_cloze' => 'boolean',
            'cloze_blank_indices' => 'array',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * @return HasMany<QuestionAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    /**
     * Scope a query to only include questions belonging to the given reference material.
     */
    #[Scope]
    protected function forReferenceMaterial(Builder $query, ReferenceMaterial $referenceMaterial): void
    {
        $query->whereHas('chapter', fn (Builder $query) => $query->where('reference_material_id', $referenceMaterial->id));
    }
}
