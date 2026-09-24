<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\SubActions\ScheduleReadingNoteReview;
use App\DTO\ReadingNoteForm;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddReadingNote
{
    public function __construct(
        private ScheduleReadingNoteReview $scheduleReview,
    ) {}

    public function handle(int $referenceMaterialId, ReadingNoteForm $form, int $accessTokenId): Outcome
    {
        try {
            $material = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($referenceMaterialId);

            $note = $material->readingNotes()->create([
                ...$form->toAttributes(),
                'access_token_id' => $accessTokenId,
                'page_snapshot' => $material->current_page,
            ]);

            $this->scheduleReview->handle($note);

            return Outcome::success(message: 'Anotação registrada.', data: $note);
        } catch (\Exception $e) {
            Log::error("Erro ao registrar anotação de leitura: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível registrar a anotação.');
        }
    }
}
