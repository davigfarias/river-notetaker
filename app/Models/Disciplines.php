<?php

namespace App\Models;

use App\Enums\Weekday;
use Carbon\CarbonImmutable;
use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property-read int $id
 * @property string $title
 * @property int|null $period
 * @property string|null $code
 * @property string|null $professor
 * @property Weekday|null $class_weekday
 * @property CarbonImmutable|null $completed_at
 * @property string $slug
 * @property string $icon
 */
#[UseFactory(DisciplineFactory::class)]
#[Fillable([
    'title',
    'period',
    'code',
    'professor',
    'class_weekday',
    'completed_at',
    'slug',
    'icon',
])]
#[Table(name: 'disciplines')]
class Disciplines extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory, Searchable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'integer',
            'class_weekday' => Weekday::class,
            'completed_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): DisciplineFactory
    {
        return DisciplineFactory::new();
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     period: int|null,
     *     code: string|null,
     *     professor: string|null,
     *     class_weekday: int|null,
     *     slug: string,
     *     icon: string
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'period' => $this->period,
            'code' => $this->code,
            'professor' => $this->professor,
            'class_weekday' => $this->class_weekday?->value,
            'slug' => $this->slug,
            'icon' => $this->icon,
        ];
    }

    /**
     * @return HasMany<Notes, $this>
     */
    public function note(): HasMany
    {
        return $this->hasMany(Notes::class);
    }

    /**
     * Temas de princípios que essa disciplina usa como fonte pra linkar
     * trechos de nota (ex.: Teologia Apocalíptica pode usar temas de
     * Hermenêutica e de Teologia Sistemática ao mesmo tempo).
     *
     * @return BelongsToMany<PrincipleTopic, $this>
     */
    public function principleTopics(): BelongsToMany
    {
        return $this->belongsToMany(PrincipleTopic::class, 'discipline_principle_topic', 'discipline_id', 'principle_topic_id');
    }

    /**
     * Uma disciplina só alimenta a fila de revisão enquanto tem dia de aula
     * definido e não foi encerrada.
     */
    public function isInReviewRotation(): bool
    {
        return $this->class_weekday instanceof Weekday && ! $this->completed_at instanceof CarbonImmutable;
    }
}
