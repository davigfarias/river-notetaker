<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\AccessToken;
use Livewire\Wireable;

/**
 * @phpstan-type AccessTokenArray array{
 *     id: int|string|null,
 *     name: string|null,
 *     lastUsedAt: string|null,
 *     revokedAt: string|null,
 *     createdAt: string|null
 * }
 */
readonly class AccessTokenDTO implements Wireable
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $lastUsedAt = null,
        public ?string $revokedAt = null,
        public ?string $createdAt = null,
    ) {}

    /**
     * @return AccessTokenArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastUsedAt' => $this->lastUsedAt,
            'revokedAt' => $this->revokedAt,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function fromModel(AccessToken $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            lastUsedAt: $model->last_used_at?->format('d/m/Y H:i'),
            revokedAt: $model->revoked_at?->format('d/m/Y H:i'),
            createdAt: $model->created_at?->format('d/m/Y H:i'),
        );
    }

    /**
     * @return AccessTokenArray
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * @param  AccessTokenArray  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            id: $value['id'] ?? null,
            name: $value['name'] ?? null,
            lastUsedAt: $value['lastUsedAt'] ?? null,
            revokedAt: $value['revokedAt'] ?? null,
            createdAt: $value['createdAt'] ?? null,
        );
    }
}
