<?php

declare(strict_types=1);

namespace App\Support;

final readonly class Outcome
{
    private function __construct(
        public bool $success,
        public int|string|null $message,
        public mixed $data = null,
    ) {}

    public static function noViewMessage(mixed $data = null): self
    {
        return new self(true, null, $data);
    }

    public static function success(string|\BackedEnum|null $message = null, mixed $data = null): self
    {
        if ($message instanceof \BackedEnum) {
            $message = $message->value;
        }

        return new self(true, $message, $data);
    }

    public static function failure(string|\BackedEnum $message, mixed $data = null): self
    {
        if ($message instanceof \BackedEnum) {
            $message = $message->value;
        }

        return new self(false, $message, $data);
    }
}
