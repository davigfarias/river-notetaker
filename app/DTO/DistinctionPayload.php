<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\Concerns\ValidatesPayload;
use App\Exceptions\InvalidExercisePayload;

/** Exercício "Analisar": um caso em que só um dos princípios candidatos é o mais pertinente. */
final readonly class DistinctionPayload
{
    use ValidatesPayload;

    /**
     * @param  list<array{principle_id: int, title: string}>  $candidates  já embaralhados
     */
    public function __construct(
        public string $scenario,
        public array $candidates,
        public int $correctPrincipleId,
        public string $rationale,
    ) {
        if (count($candidates) < 2 || ! in_array($correctPrincipleId, array_column($candidates, 'principle_id'), true)) {
            throw new InvalidExercisePayload('O princípio correto precisa estar entre 2 ou mais candidatos.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $raw = $data['candidates'] ?? null;

        if (! is_array($raw) || count($raw) > 4) {
            throw new InvalidExercisePayload('Campo candidates inválido.');
        }

        $candidates = array_values(array_map(fn (mixed $c): array => is_array($c)
            ? ['principle_id' => self::integer($c, 'principle_id'), 'title' => self::text($c, 'title', 255)]
            : throw new InvalidExercisePayload('Candidato inválido.'), $raw));

        return new self(
            self::text($data, 'scenario', 1500),
            $candidates,
            self::integer($data, 'correct_principle_id'),
            self::text($data, 'rationale', 1500),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenario' => $this->scenario,
            'candidates' => $this->candidates,
            'correct_principle_id' => $this->correctPrincipleId,
            'rationale' => $this->rationale,
        ];
    }

    /**
     * @return list<int>
     */
    public function candidateIds(): array
    {
        return array_map(fn (array $c): int => $c['principle_id'], $this->candidates);
    }
}
