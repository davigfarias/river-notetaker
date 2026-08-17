<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccessTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(AccessTokenFactory::class)]
#[Fillable(['name', 'token', 'last_used_at', 'revoked_at'])]
#[Hidden(['token'])]
#[Table(name: 'access_tokens')]
class AccessToken extends Model
{
    use HasFactory;

    public function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Notes, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Notes::class);
    }
}
