<?php

declare(strict_types=1);

namespace App\Models;

use App\ValueObjects\Date;
use Database\Factories\DisciplineFactory;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

#[UseFactory(NoteFactory::class)]
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'title',
    'tags',
    'discipline_id',
    'impressions',
    'life_experiences',
    'updated_at',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'notes')]
class Notes extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory, Searchable;

    public function casts(): array
    {
        return [
            'tags' => 'array',
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

    public function concepts(): HasMany
    {
        return $this->hasMany(Concepts::class, 'note_id');
    }

    public function pastoral_advice(): HasMany
    {
        return $this->hasMany(PastoralAdvices::class, 'note_id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(References::class, 'note_id');
    }

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
