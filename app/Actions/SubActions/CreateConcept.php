<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\ConceptsDTO;
use App\Models\Concepts;

final readonly class CreateConcept
{
    /**
     * @param  ConceptsDTO[]  $conceptsDtos
     */
    public function handle(int $noteId, array $conceptsDtos): void
    {
        foreach ($conceptsDtos as $dto) {
            Concepts::create([
                'note_id' => $noteId,
                'term' => $dto->term,
                'definition' => $dto->definition,
            ]);
        }
    }
}
