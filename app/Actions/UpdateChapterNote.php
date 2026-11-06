<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\SubActions\ScheduleReadingNoteReview;
use App\DTO\ChapterNoteForm;
use App\Models\Chapter;
use App\Support\Outcome;
use App\Support\ReviewSubject;
use Illuminate\Support\Facades\Log;

/**
 * Salva título e anotação de um capítulo de livro. A primeira vez que o
 * capítulo ganha texto ele entra na escada de revisão (amanhã); apagar o
 * texto tira o capítulo da fila, e ele não gera mais MCQ.
 */
final readonly class UpdateChapterNote
{
    public function __construct(
        private ScheduleReadingNoteReview $scheduleReview,
    ) {}

    public function handle(int $chapterId, ChapterNoteForm $form, int $accessTokenId): Outcome
    {
        try {
            $chapter = ReviewSubject::owned(ReviewSubject::CHAPTER, $accessTokenId)->findOrFail($chapterId);
            \assert($chapter instanceof Chapter);

            $hadNote = $chapter->hasNote();
            $attributes = $form->toAttributes();

            if (! $hadNote && $attributes['body'] !== null) {
                $attributes['page_snapshot'] = $chapter->referenceMaterial->current_page;
            }

            $chapter->update($attributes);

            if (! $chapter->hasNote()) {
                $chapter->forceFill(['review_stage' => 0, 'next_review_at' => null, 'consolidated_at' => null])->saveQuietly();
            } elseif (! $hadNote) {
                $this->scheduleReview->handle($chapter);
            }

            return Outcome::success(message: 'Capítulo atualizado.', data: $chapter);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar a anotação do capítulo: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o capítulo.');
        }
    }
}
