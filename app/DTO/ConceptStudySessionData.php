<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ConceptStudySessionData
{
    /**
     * @param  array<int, int>  $cardIds
     */
    public function __construct(
        public string $deckName,
        public array $cardIds,
    ) {}
}
