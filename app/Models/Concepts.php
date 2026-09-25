<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $note_id
 * @property string $term
 * @property string $definition
 */
#[Fillable([
    'note_id',
    'term',       // Corrigido
    'definition', // Corrigido
])]
#[Table(name: 'concepts')]
class Concepts extends Model
{
    use Searchable;

    /**
     * @return BelongsTo<Notes, $this>
     */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Notes::class, 'note_id'); // Boa prática: explicitar a foreign_key
    }

    /**
     * @return BelongsToMany<Concepts, $this>
     */
    public function relatedConcepts(): BelongsToMany
    {
        return $this->belongsToMany(Concepts::class, 'concept_concept', 'concept_id', 'related_concept_id');
    }

    /**
     * @return HasMany<Principle, $this>
     */
    public function principles(): HasMany
    {
        return $this->hasMany(Principle::class, 'concept_id');
    }

    /**
     * @return array{
     *     id: int,
     *     note_id: int,
     *     term: string,
     *     definition: string
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'note_id' => $this->note_id,
            'term' => $this->term,
            'definition' => $this->definition,
        ];
    }
}
