<?php

namespace App\Models;

use Database\Factories\PrincipleTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 */
#[UseFactory(PrincipleTopicFactory::class)]
#[Fillable(['title', 'slug'])]
#[Table(name: 'principle_topics')]
class PrincipleTopic extends Model
{
    /** @use HasFactory<PrincipleTopicFactory> */
    use HasFactory;

    /**
     * @return HasMany<Principle, $this>
     */
    public function principles(): HasMany
    {
        return $this->hasMany(Principle::class)->orderBy('position');
    }

    protected static function newFactory(): PrincipleTopicFactory
    {
        return PrincipleTopicFactory::new();
    }
}
