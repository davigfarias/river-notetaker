<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\ValueObjects\Casts\ValueObjectCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Stringable;

abstract readonly class ValueObject implements Castable, Stringable
{
    abstract public function value(): mixed;

    public function equals(ValueObject $other): bool
    {
        return static::class === $other::class && $this->value() === $other->value();
    }

    /**
     * @return CastsAttributes<static, mixed>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new ValueObjectCast(static::class);
    }
}
