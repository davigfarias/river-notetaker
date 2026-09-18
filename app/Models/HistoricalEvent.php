<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Era;
use App\Enums\HistoricalEventNature;
use App\ValueObjects\HistoricalYear;
use Carbon\CarbonImmutable;
use Database\Factories\HistoricalEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $access_token_id
 * @property string $title
 * @property string|null $description
 * @property HistoricalEventNature $nature
 * @property int $start_year
 * @property Era $start_era
 * @property int|null $end_year
 * @property Era|null $end_era
 * @property int $sort_key
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(HistoricalEventFactory::class)]
#[Fillable([
    'access_token_id',
    'title',
    'description',
    'nature',
    'start_year',
    'start_era',
    'end_year',
    'end_era',
])]
#[Table(name: 'historical_events')]
class HistoricalEvent extends Model
{
    /** @use HasFactory<HistoricalEventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (HistoricalEvent $event): void {
            $event->sort_key = $event->start()->sortKey();
        });
    }

    public function casts(): array
    {
        return [
            'nature' => HistoricalEventNature::class,
            'start_year' => 'integer',
            'start_era' => Era::class,
            'end_year' => 'integer',
            'end_era' => Era::class,
            'sort_key' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    public function start(): HistoricalYear
    {
        return new HistoricalYear($this->start_year, $this->start_era);
    }

    public function end(): ?HistoricalYear
    {
        if ($this->end_year === null || $this->end_era === null) {
            return null;
        }

        return new HistoricalYear($this->end_year, $this->end_era);
    }

    public function periodLabel(): string
    {
        return HistoricalYear::rangeLabel($this->start(), $this->end());
    }
}
