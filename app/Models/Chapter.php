<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reference_material_id
 * @property string $title
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ChapterFactory::class)]
#[Fillable([
    'reference_material_id',
    'title',
    'position',
])]
#[Table(name: 'chapters')]
class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'position' => 'integer',
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
}
