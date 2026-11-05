<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConceptDeckCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $concept_deck_id
 * @property int $concept_id
 * @property bool $is_active
 * @property bool $is_starred
 */
#[UseFactory(ConceptDeckCardFactory::class)]
#[Fillable(['concept_deck_id', 'concept_id', 'is_active', 'is_starred'])]
#[Table(name: 'concept_deck_cards')]
class ConceptDeckCard extends Model
{
    /** @use HasFactory<ConceptDeckCardFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_starred' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ConceptDeck, $this>
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(ConceptDeck::class, 'concept_deck_id');
    }

    /**
     * @return BelongsTo<Concepts, $this>
     */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concepts::class, 'concept_id');
    }

    /**
     * @param  Builder<ConceptDeckCard>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('concept_deck_cards.is_active', true);
    }
}
