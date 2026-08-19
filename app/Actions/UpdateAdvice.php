<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SoleAdviceDTO;
use App\Models\PastoralAdvices;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateAdvice
{
    public function handle(int $id, SoleAdviceDTO $advice): Outcome
    {
        try {
            $model = PastoralAdvices::findOrFail($id);

            $model->update([
                'category' => $advice->category,
                'advice' => $advice->advice,
            ]);

            return Outcome::success(message: 'Conselho pastoral atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar o conselho pastoral: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o conselho pastoral.');
        }
    }
}
