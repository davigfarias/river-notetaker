<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReadingStatus;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateReadingProgress
{
    public function handle(
        int $id,
        int $accessTokenId,
        ReadingStatus $status,
        ?int $currentPage,
    ): Outcome {
        try {
            $material = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($id);

            $range = $material->readingRange();

            if ($currentPage !== null) {
                $material->current_page = max($range['start'], min($currentPage, $range['end']));
            }

            $material->reading_status = $status;

            if ($status === ReadingStatus::Reading && $material->reading_started_at === null) {
                $material->reading_started_at = today();
            }

            if ($status === ReadingStatus::Read) {
                $material->reading_started_at ??= today();
                $material->reading_finished_at ??= today();
                $material->current_page = $range['end'];
            }

            $material->save();

            return Outcome::success(message: 'Progresso de leitura atualizado.', data: $material);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar o progresso de leitura: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o progresso de leitura.');
        }
    }
}
