<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
