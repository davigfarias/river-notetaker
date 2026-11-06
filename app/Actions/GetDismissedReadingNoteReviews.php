<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DueReadingNoteReviewDTO;
use App\Support\Outcome;
use App\Support\ReviewSubject;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Itens (anotações de artigo e capítulos de livro) que o usuário tirou da
 * fila de revisão, para ele poder desfazer.
 */
final readonly class GetDismissedReadingNoteReviews
{
    public function handle(?int $referenceMaterialId, int $accessTokenId): Outcome
    {
        try {
            $today = CarbonImmutable::now()->startOfDay();

            $dismissed = collect([ReviewSubject::NOTE, ReviewSubject::CHAPTER])
                ->flatMap(fn (string $kind) => ReviewSubject::owned($kind, $accessTokenId)
                    ->when($referenceMaterialId !== null, fn ($query) => $query->where('reference_material_id', $referenceMaterialId))
                    ->whereNotNull('dismissed_at')
                    ->with('referenceMaterial:id,title')
                    ->get()
                    ->map(fn (Model $item): array => [
                        'at' => $item->dismissed_at,
                        'dto' => DueReadingNoteReviewDTO::fromModel($item, $today, $kind),
                    ]))
                ->sortByDesc('at')
                ->pluck('dto')
                ->values();

            return Outcome::noViewMessage($dismissed);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível listar as anotações dispensadas.');
        }
    }
}
