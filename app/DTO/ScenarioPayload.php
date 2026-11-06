<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\Concerns\ValidatesPayload;

/** Exercício "Aplicar": um caso concreto. A chave de aplicação só aparece depois da resposta. */
final readonly class ScenarioPayload
{
    use ValidatesPayload;

    /**
     * @param  list<string>  $keyPoints
     */
    public function __construct(
        public string $scenario,
        public string $question,
        public string $expectedApplication,
        public array $keyPoints,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::text($data, 'scenario', 1500),
            self::text($data, 'question', 300),
            self::text($data, 'expected_application', 1500),
            self::textList($data, 'key_points', 6, 300),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenario' => $this->scenario,
            'question' => $this->question,
            'expected_application' => $this->expectedApplication,
            'key_points' => $this->keyPoints,
        ];
    }
}
