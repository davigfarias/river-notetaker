<?php

namespace App\Models;

use Database\Factories\PrincipleCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $principle_topic_id
 * @property string $title
 * @property int $position
 */
#[UseFactory(PrincipleCategoryFactory::class)]
#[Fillable(['principle_topic_id', 'title', 'position'])]
#[Table(name: 'principle_categories')]
class PrincipleCategory extends Model
{
    /** @use HasFactory<PrincipleCategoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<PrincipleTopic, $this>
     */
    public function principleTopic(): BelongsTo
    {
        return $this->belongsTo(PrincipleTopic::class);
    }

    /**
     * @return HasMany<Principle, $this>
     */
    public function principles(): HasMany
    {
        return $this->hasMany(Principle::class)->orderBy('position');
    }

    protected static function newFactory(): PrincipleCategoryFactory
    {
        return PrincipleCategoryFactory::new();
    }
}
