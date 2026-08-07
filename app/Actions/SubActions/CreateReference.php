<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\ReferencesDTO;
use App\Repository\AppRepository;

final readonly class CreateReference
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    /**
     * @param  ReferencesDTO[]  $referencesDtos
     */
    public function handle(int $noteId, array $referencesDtos): void
    {
        foreach ($referencesDtos as $dto) {
            $this->appRepository->createReference($noteId, $dto);
        }
    }
}
