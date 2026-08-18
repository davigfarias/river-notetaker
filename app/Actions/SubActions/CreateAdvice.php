<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\AdvicesDTO;
use App\Models\PastoralAdvices;

final readonly class CreateAdvice
{
    /**
     * @param  AdvicesDTO[]  $advicesDtos
     */
    public function handle(int $noteId, array $advicesDtos): void
    {
        foreach ($advicesDtos as $dto) {
            PastoralAdvices::create([
                'note_id' => $noteId,
                'category' => $dto->category,
                'advice' => $dto->advice,
            ]);
        }
    }
}
