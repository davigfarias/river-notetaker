<?php

namespace App\Models;

use Database\Factories\PrincipleTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Um tema pode alimentar princípios em várias disciplinas (ex.: um tema
     * de Hermenêutica pode ser usado tanto na disciplina de Hermenêutica
     * quanto na de Teologia Apocalíptica).
     *
     * @return BelongsToMany<Disciplines, $this>
     */
    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Disciplines::class, 'discipline_principle_topic', 'principle_topic_id', 'discipline_id');
    }

    protected static function newFactory(): PrincipleTopicFactory
    {
        return PrincipleTopicFactory::new();
    }
}
