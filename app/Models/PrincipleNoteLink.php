<?php

namespace App\Models;

use App\Enums\NoteAnnotatableField;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $principle_id
 * @property int $note_id
 * @property NoteAnnotatableField $field
 * @property string $snippet
 */
#[Fillable(['principle_id', 'note_id', 'field', 'snippet'])]
#[Table(name: 'principle_note_links')]
class PrincipleNoteLink extends Model
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
     * @return BelongsTo<Principle, $this>
     */
    public function principle(): BelongsTo
    {
        return $this->belongsTo(Principle::class);
    }

    /**
     * @return BelongsTo<Notes, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Notes::class);
    }
}
