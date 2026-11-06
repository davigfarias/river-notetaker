<?php

declare(strict_types=1);

namespace App\Models;

use App\DTO\ReviewSnapshot;
use App\Enums\SelfRating;
use Carbon\CarbonImmutable;
use Database\Factories\PrincipleReviewStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agenda de revisão de um princípio para um token (a unidade agendada é o princípio).
 *
 * @property int $id
 * @property int $access_token_id
 * @property int $principle_id
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable|null $last_reviewed_at
 * @property int $interval_days
 * @property float $ease
 * @property int $repetitions
 * @property int $lapses
 * @property SelfRating|null $last_rating
 */
#[Fillable([
    'access_token_id', 'principle_id', 'due_at', 'last_reviewed_at',
    'interval_days', 'ease', 'repetitions', 'lapses', 'last_rating',
])]
#[Table(name: 'principle_review_states')]
#[UseFactory(PrincipleReviewStateFactory::class)]
class PrincipleReviewState extends Model
{
    /** @use HasFactory<PrincipleReviewStateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_datetime',
            'last_reviewed_at' => 'immutable_datetime',
            'interval_days' => 'integer',
            'ease' => 'float',
            'repetitions' => 'integer',
            'lapses' => 'integer',
            'last_rating' => SelfRating::class,
        ];
    }

    /**
     * @return BelongsTo<Principle, $this>
     */
    public function principle(): BelongsTo
    {
        return $this->belongsTo(Principle::class);
    }

    public function toSnapshot(): ReviewSnapshot
    {
        return new ReviewSnapshot(
            $this->interval_days,
            $this->ease,
            $this->repetitions,
            $this->lapses,
            $this->due_at,
            $this->last_reviewed_at,
        );
    }
}
