<?php

declare(strict_types=1);

namespace App\DTO\Concerns;

use App\Exceptions\InvalidExercisePayload;

/** Leitura estrita dos campos de um payload de exercício vindo da IA ou do banco. */
trait ValidatesPayload
{
    /**
     * @param  array<string, mixed>  $data
     */
    private static function text(array $data, string $key, int $max): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
            throw new InvalidExercisePayload("Campo {$key} ausente, vazio ou longo demais.");
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function textList(array $data, string $key, int $maxItems, int $maxLength): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value) || count($value) > $maxItems) {
            throw new InvalidExercisePayload("Campo {$key} inválido.");
        }

        return array_values(array_map(function (mixed $item) use ($key, $maxLength): string {
            if (! is_string($item) || trim($item) === '' || mb_strlen($item) > $maxLength) {
                throw new InvalidExercisePayload("Campo {$key} tem item inválido.");
            }

            return trim($item);
        }, $value));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function integer(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            throw new InvalidExercisePayload("Campo {$key} precisa ser um número inteiro.");
        }

        return (int) $value;
    }
}
