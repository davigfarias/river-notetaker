<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrincipleSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma sessão de estudo. `composition` guarda os itens planejados:
 * lista de {principle_id, type}.
 *
 * @property int $id
 * @property int $access_token_id
 * @property int|null $discipline_id
 * @property int|null $principle_category_id
 * @property int $planned_items
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $finished_at
 * @property list<array{principle_id: int, type: string, retry?: bool, exercise_id?: int, substituted?: bool}> $composition
 * @property-read int|null $answered_count Só existe quando carregado por FindOpenPrincipleSession.
 */
#[Fillable([
    'access_token_id', 'discipline_id', 'principle_category_id',
    'planned_items', 'started_at', 'finished_at', 'composition',
])]
#[Table(name: 'principle_sessions')]
#[UseFactory(PrincipleSessionFactory::class)]
class PrincipleSession extends Model
{
    /** @use HasFactory<PrincipleSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_items' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'composition' => 'array',
        ];
    }

    /**
     * @return HasMany<PrincipleAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(PrincipleAttempt::class);
    }
}
