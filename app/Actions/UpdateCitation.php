<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\CitationForm;
use App\Models\Citation;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateCitation
{
    public function handle(int $id, CitationForm $form, int $accessTokenId): Outcome
    {
        try {
            $citation = Citation::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($id);

            $citation->update($form->toAttributes());

            return Outcome::success(message: 'Citação atualizada.', data: $citation);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar citação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar a citação.');
        }
    }
}
