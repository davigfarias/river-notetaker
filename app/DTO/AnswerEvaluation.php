<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\AiVerdict;
use App\Enums\SelfRating;
use App\Exceptions\InvalidAiEvaluation;

/**
 * Avaliação da IA para uma resposta livre. Só nasce de fromArray(), que valida tudo.
 */
final readonly class AnswerEvaluation
{
    private const MAX_POINTS = 8;

    private const MAX_POINT_LENGTH = 500;

    private const MAX_FEEDBACK_LENGTH = 1200;

    /**
     * @param  list<string>  $coveredPoints
     * @param  list<string>  $missingPoints
     * @param  list<string>  $misconceptions
     */
    public function __construct(
        public AiVerdict $verdict,
        public array $coveredPoints,
        public array $missingPoints,
        public array $misconceptions,
        public string $feedback,
        public SelfRating $suggestedRating,
        public float $confidence,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidAiEvaluation
     */
    public static function fromArray(array $data): self
    {
        $verdict = AiVerdict::tryFrom((string) ($data['verdict'] ?? ''))
            ?? throw new InvalidAiEvaluation('verdict inválido.');
        $rating = SelfRating::tryFrom((string) ($data['suggested_rating'] ?? ''))
            ?? throw new InvalidAiEvaluation('suggested_rating inválido.');

        $confidence = $data['confidence'] ?? null;
        if (! is_numeric($confidence) || $confidence < 0 || $confidence > 1) {
            throw new InvalidAiEvaluation('confidence precisa estar entre 0 e 1.');
        }

        $feedback = $data['feedback'] ?? null;
        if (! is_string($feedback) || trim($feedback) === '' || mb_strlen($feedback) > self::MAX_FEEDBACK_LENGTH) {
            throw new InvalidAiEvaluation('feedback vazio ou longo demais.');
        }

        return new self(
            $verdict,
            self::points($data, 'covered_points'),
            self::points($data, 'missing_points'),
            self::points($data, 'misconceptions'),
            trim($feedback),
            $rating,
            (float) $confidence,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function points(array $data, string $key): array
    {
        $points = $data[$key] ?? throw new InvalidAiEvaluation("{$key} ausente.");

        if (! is_array($points) || count($points) > self::MAX_POINTS) {
            throw new InvalidAiEvaluation("{$key} inválido.");
        }

        return array_values(array_map(function (mixed $point) use ($key): string {
            if (! is_string($point) || trim($point) === '' || mb_strlen($point) > self::MAX_POINT_LENGTH) {
                throw new InvalidAiEvaluation("{$key} tem item inválido.");
            }

            return trim($point);
        }, $points));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'verdict' => $this->verdict->value,
            'covered_points' => $this->coveredPoints,
            'missing_points' => $this->missingPoints,
            'misconceptions' => $this->misconceptions,
            'feedback' => $this->feedback,
            'suggested_rating' => $this->suggestedRating->value,
            'confidence' => $this->confidence,
        ];
    }

    public function isUncertain(float $threshold): bool
    {
        return $this->confidence < $threshold;
    }
}
