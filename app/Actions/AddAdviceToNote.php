<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SoleAdviceDTO;
use App\Models\PastoralAdvices;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddAdviceToNote
{
    public function handle(int $noteId, SoleAdviceDTO $advice): Outcome
    {
        try {
            $model = PastoralAdvices::create([
                'note_id' => $noteId,
                'category' => $advice->category,
                'advice' => $advice->advice,
            ]);

            return Outcome::success(data: $model, message: 'Conselho pastoral adicionado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar o conselho pastoral: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar o conselho pastoral.');
        }
    }
}
