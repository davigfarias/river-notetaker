<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Snapshot of one answer, kept by the study page so it can be undone.
 */
final readonly class ConceptAnswerData
{
    public function __construct(
        public int $reviewId,
        public int $previousAcedCount,
        public int $previousMissedCount,
        public ?string $previousLastReviewedAt,
    ) {}

    /**
     * @return array{reviewId: int, previousAcedCount: int, previousMissedCount: int, previousLastReviewedAt: ?string}
     */
    public function toArray(): array
    {
        return [
            'reviewId' => $this->reviewId,
            'previousAcedCount' => $this->previousAcedCount,
            'previousMissedCount' => $this->previousMissedCount,
            'previousLastReviewedAt' => $this->previousLastReviewedAt,
        ];
    }
}
