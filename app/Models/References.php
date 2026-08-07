<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $note_id
 * @property string $type
 * @property string $reference_text
 */
#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'note_id',
    'type',
    'reference_text',
])]
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'references')]
class References extends Model
{
    use Searchable;

    /**
     * @return BelongsTo<Notes, $this>
     */
    public function reference(): BelongsTo
    {
        return $this->belongsTo(Notes::class);
    }

    /**
     * @return array{
     *     note_id: int,
     *     type: string,
     *     reference_text: string
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'note_id' => $this->note_id,
            'type' => $this->type,
            'reference_text' => $this->reference_text,
        ];
    }
}
