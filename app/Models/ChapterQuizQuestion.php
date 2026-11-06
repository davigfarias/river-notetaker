<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChapterQuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $chapter_id
 * @property string $question
 * @property string $correct_answer
 * @property array<int, string> $distractors
 * @property string $content_hash
 * @property string $provider
 * @property string|null $model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ChapterQuizQuestionFactory::class)]
#[Fillable([
    'chapter_id',
    'question',
    'correct_answer',
    'distractors',
    'content_hash',
    'provider',
    'model',
])]
#[Table(name: 'chapter_quiz_questions')]
class ChapterQuizQuestion extends Model
{
    /** @use HasFactory<ChapterQuizQuestionFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'distractors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
