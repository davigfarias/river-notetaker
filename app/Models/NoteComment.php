<?php

namespace App\Models;

use App\Enums\NoteAnnotatableField;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $note_id
 * @property NoteAnnotatableField $field
 * @property string $snippet
 * @property string $body
 */
#[Fillable(['note_id', 'field', 'snippet', 'body'])]
#[Table(name: 'note_comments')]
class NoteComment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field' => NoteAnnotatableField::class,
        ];
    }

    /**
     * @return BelongsTo<Notes, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Notes::class);
    }
}
