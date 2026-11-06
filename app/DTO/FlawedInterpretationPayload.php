<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\Concerns\ValidatesPayload;

/** Exercício "Avaliar": uma leitura com um erro sutil que viola o princípio. */
final readonly class FlawedInterpretationPayload
{
    use ValidatesPayload;

    public function __construct(
        public string $interpretation,
        public string $flawExplanation,
        public int $violatedPrincipleId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::text($data, 'interpretation', 1500),
            self::text($data, 'flaw_explanation', 1500),
            self::integer($data, 'violated_principle_id'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'interpretation' => $this->interpretation,
            'flaw_explanation' => $this->flawExplanation,
            'violated_principle_id' => $this->violatedPrincipleId,
        ];
    }
}
