<?php

namespace App\Models;

use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property-read int $id
 * @property string $title
 * @property string $slug
 * @property string $icon
 */
#[UseFactory(DisciplineFactory::class)]
#[Fillable([
    'title',
    'slug',
    'icon',
])]
#[Table(name: 'disciplines')]
class Disciplines extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory, Searchable;

    protected static function newFactory(): DisciplineFactory
    {
        return DisciplineFactory::new();
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     icon: string
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
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
}
