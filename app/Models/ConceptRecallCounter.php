<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $access_token_id
 * @property int $concept_id
 * @property int $aced_count
 * @property int $missed_count
 * @property CarbonImmutable|null $last_reviewed_at
 */
#[Fillable(['access_token_id', 'concept_id', 'aced_count', 'missed_count', 'last_reviewed_at'])]
#[Table(name: 'concept_recall_counters')]
class ConceptRecallCounter extends Model
{
    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return ['last_reviewed_at' => 'immutable_datetime'];
    }

    /**
     * @param  Builder<ConceptRecallCounter>  $query
     */
    public function scopeToReinforce(Builder $query): void
    {
        $query->whereColumn('missed_count', '>', 'aced_count');
    }
}
