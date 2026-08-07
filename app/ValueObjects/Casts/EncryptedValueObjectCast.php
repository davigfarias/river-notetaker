<?php

declare(strict_types=1);

namespace App\ValueObjects\Casts;

use App\ValueObjects\ValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @template TValueObject of ValueObject
 *
 * @implements CastsAttributes<TValueObject, mixed>
 */
class EncryptedValueObjectCast implements CastsAttributes
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
        if ($value === null) {
            return null;
        }

        $decrypted = Crypt::decryptString($value);

        return new $this->class($decrypted);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof $this->class) {
            $value = new $this->class($value);
        }

        return Crypt::encryptString($value->value());
    }
}
