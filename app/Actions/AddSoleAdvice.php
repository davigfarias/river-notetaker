<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SoleAdviceDTO;
use App\Models\PastoralAdvices;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddSoleAdvice
{
    public function handle(SoleAdviceDTO $advice): Outcome
    {
        try {
            $query = PastoralAdvices::create([
                'category' => $advice->category,
                'advice' => $advice->advice,
            ]);

            return Outcome::success(data: $query, message: "Conselho sobre '{$advice->category}' criado com sucesso");
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar o conselho pastoral.');
        }
    }
}
