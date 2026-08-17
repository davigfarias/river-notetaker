<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use InvalidArgumentException;

readonly class Date extends ValueObject
{
    private CarbonInterface $date;

    public function __construct(CarbonInterface|string $date)
    {
        if (is_string($date)) {
            try {
                $date = Carbon::parse($date);
            } catch (Exception) {
                throw new InvalidArgumentException("A data informada é inválida: {$date}");
            }
        }

        $this->date = $date;
    }

    public static function fromFormat(string $format, string $date): self
    {
        $parsed = Carbon::createFromFormat($format, $date);

        if (! $parsed instanceof Carbon) {
            throw new InvalidArgumentException("A data informada é inválida: {$date}");
        }

        return new self($parsed);
    }

    public function value(): string
    {
        return $this->date->format('Y-m-d H:i:s');
    }

    public function toCarbon(): CarbonInterface
    {
        return $this->date;
    }

    public function toDate(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function toDisplay(): string
    {
        return $this->date->format('d/m/Y');
    }

    public function toDisplayDateTime(): string
    {
        return $this->date->format('d/m/Y H:i:s');
    }

    public function toIso(): string
    {
        return $this->date->toIso8601String();
    }

    public function format(string $format): string
    {
        return $this->date->format($format);
    }

    public function startOfDay(): self
    {
        return new self($this->date->copy()->startOfDay());
    }

    public function endOfDay(): self
    {
        return new self($this->date->copy()->endOfDay());
    }

    public function addDays(int $days): self
    {
        return new self($this->date->copy()->addDays($days));
    }

    public function subDays(int $days): self
    {
        return new self($this->date->copy()->subDays($days));
    }

    public function isBefore(Date $other): bool
    {
        return $this->date->lt($other->toCarbon());
    }

    public function isAfter(Date $other): bool
    {
        return $this->date->gt($other->toCarbon());
    }

    public function isBetween(Date $start, Date $end): bool
    {
        return $this->date->between($start->toCarbon(), $end->toCarbon());
    }

    public function diffInDays(Date $other): int
    {
        return (int) $this->date->diffInDays($other->toCarbon());
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
