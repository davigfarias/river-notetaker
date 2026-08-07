<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\DTO\AdvicesDTO;
use App\Repository\AppRepository;

final readonly class CreateAdvice
{
    public function __construct(
        private AppRepository $appRepository
    ) {}

    /**
     * @param  AdvicesDTO[]  $advicesDtos
     */
    public function handle(int $noteId, array $advicesDtos): void
    {
        foreach ($advicesDtos as $dto) {
            $this->appRepository->createAdvice($noteId, $dto);
        }
    }
}
