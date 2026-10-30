<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DueReadingNoteReviewDTO;
use App\Models\ReadingNote;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Anotações que o usuário tirou da fila de revisão, para ele poder desfazer.
 */
final readonly class GetDismissedReadingNoteReviews
{
    public function handle(?int $referenceMaterialId, int $accessTokenId): Outcome
    {
        try {
            $today = CarbonImmutable::now()->startOfDay();

            $dismissed = ReadingNote::query()
                ->when($referenceMaterialId !== null, fn ($query) => $query->where('reference_material_id', $referenceMaterialId))
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->whereNotNull('dismissed_at')
                ->with('referenceMaterial:id,title')
                ->orderByDesc('dismissed_at')
                ->get()
                ->map(fn (ReadingNote $note): DueReadingNoteReviewDTO => DueReadingNoteReviewDTO::fromModel($note, $today));

            return Outcome::noViewMessage($dismissed);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível listar as anotações dispensadas.');
        }
    }
}
