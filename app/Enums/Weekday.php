<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Dia da semana em que a disciplina tem aula, no padrão ISO-8601
 * (segunda-feira é 1, domingo é 7), que é o mesmo do Carbon.
 */
enum Weekday: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Segunda-feira',
            self::Tuesday => 'Terça-feira',
            self::Wednesday => 'Quarta-feira',
            self::Thursday => 'Quinta-feira',
            self::Friday => 'Sexta-feira',
            self::Saturday => 'Sábado',
            self::Sunday => 'Domingo',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Monday => 'Seg',
            self::Tuesday => 'Ter',
            self::Wednesday => 'Qua',
            self::Thursday => 'Qui',
            self::Friday => 'Sex',
            self::Saturday => 'Sáb',
            self::Sunday => 'Dom',
        };
    }

    public static function fromDate(CarbonInterface $date): self
    {
        return self::from($date->isoWeekday());
    }

    public function matches(CarbonInterface $date): bool
    {
        return $date->isoWeekday() === $this->value;
    }

    /**
     * Primeira ocorrência deste dia da semana a partir da data informada,
     * incluindo a própria data quando ela já cai no dia da aula.
     */
    public function onOrAfter(CarbonInterface $date): CarbonImmutable
    {
        $start = CarbonImmutable::instance($date)->startOfDay();

        // Aritmética direta em dias ISO: Carbon::next() usa a convenção 0-6,
        // em que domingo é 0, e estoura com o 7 do padrão ISO.
        $daysAhead = ($this->value - $start->isoWeekday() + 7) % 7;

        return $start->addDays($daysAhead);
    }

    /**
     * @return array<int, self>
     */
    public static function options(): array
    {
        return self::cases();
    }
}
