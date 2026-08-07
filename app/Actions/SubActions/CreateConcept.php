<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\ConceptsDTO;
use App\Repository\AppRepository;

final readonly class CreateConcept
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    /**
     * @param  ConceptsDTO[]  $conceptsDtos
     */
    public function handle(int $noteId, array $conceptsDtos): void
    {
        foreach ($conceptsDtos as $dto) {
            $this->appRepository->createConcept($noteId, $dto);
        }
    }
}
