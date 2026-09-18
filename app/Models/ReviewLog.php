<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro append-only de cada revisão feita. É polimórfico de propósito: a fase
 * 1 revisa apenas notas de aula, mas notas de leitura e conceitos entram depois
 * sem migração nova.
 *
 * @property-read int $id
 * @property string $reviewable_type
 * @property int $reviewable_id
 * @property int|null $access_token_id
 * @property bool $recalled
 * @property int $stage_before
 * @property int $stage_after
 */
#[Fillable([
    'reviewable_type',
    'reviewable_id',
    'access_token_id',
    'recalled',
    'stage_before',
    'stage_after',
    'due_at',
    'reviewed_at',
])]
#[Table(name: 'review_logs')]
class ReviewLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recalled' => 'boolean',
            'stage_before' => 'integer',
            'stage_after' => 'integer',
            'due_at' => 'immutable_date',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }
}
