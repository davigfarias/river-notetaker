<?php

declare(strict_types=1);

namespace App\ValueObjects\Casts;

use App\ValueObjects\ValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TValueObject of ValueObject
 *
 * @implements CastsAttributes<TValueObject, mixed>
 */
class ValueObjectCast implements CastsAttributes
{
    /**
     * @param  class-string<TValueObject>  $class
     */
    public function __construct(
        protected string $class
    ) {}

    /**
     * @return TValueObject|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ValueObject
    {
        if (blank($value)) {
            return null;
        }

        return new $this->class($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof $this->class) {
            return $value->value();
        }

        if (blank($value)) {
            return null;
        }

        return (new $this->class($value))->value();
    }
}
