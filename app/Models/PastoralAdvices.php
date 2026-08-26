<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * @property int $id,
 * @property int $note_id,
 * @property string $category,
 * @property string $advice
 */
#[Fillable([
    'note_id',
    'category',
    'advice',
])]
#[Table(name: 'pastoral_advices')]
class PastoralAdvices extends Model
{
    use Searchable;

    /**
     * @return BelongsTo<Notes, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Notes::class, 'note_id');
    }

    /**
     * @return array{
     *     id: int,
     *     note_id: int,
     *     category: string,
     *     advice: string
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'note_id' => $this->note_id,
            'category' => $this->category,
            'advice' => $this->advice,
        ];
    }
}
