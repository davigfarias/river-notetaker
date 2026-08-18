<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\ReferencesDTO;
use App\Models\References;

final readonly class CreateReference
{
    /**
     * @param  ReferencesDTO[]  $referencesDtos
     */
    public function handle(int $noteId, array $referencesDtos): void
    {
        foreach ($referencesDtos as $dto) {
            References::create([
                'note_id' => $noteId,
                'type' => $dto->type,
                'reference_text' => $dto->reference_text,
            ]);
        }
    }
}
