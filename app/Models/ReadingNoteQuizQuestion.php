<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReadingNoteQuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reading_note_id
 * @property string $question
 * @property string $correct_answer
 * @property array<int, string> $distractors
 * @property string $content_hash
 * @property string $provider
 * @property string|null $model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ReadingNoteQuizQuestionFactory::class)]
#[Fillable([
    'reading_note_id',
    'question',
    'correct_answer',
    'distractors',
    'content_hash',
    'provider',
    'model',
])]
#[Table(name: 'reading_note_quiz_questions')]
class ReadingNoteQuizQuestion extends Model
{
    /** @use HasFactory<ReadingNoteQuizQuestionFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'distractors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ReadingNote, $this>
     */
    public function readingNote(): BelongsTo
    {
        return $this->belongsTo(ReadingNote::class);
    }
}
