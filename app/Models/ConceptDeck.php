<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConceptDeckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $access_token_id
 * @property string $name
 */
#[UseFactory(ConceptDeckFactory::class)]
#[Fillable(['access_token_id', 'name'])]
#[Table(name: 'concept_decks')]
class ConceptDeck extends Model
{
    /** @use HasFactory<ConceptDeckFactory> */
    use HasFactory;

    /**
     * @return HasMany<ConceptDeckCard, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(ConceptDeckCard::class);
    }

    /**
     * @return BelongsToMany<Concepts, $this>
     */
    public function concepts(): BelongsToMany
    {
        return $this->belongsToMany(Concepts::class, 'concept_deck_cards', 'concept_deck_id', 'concept_id')
            ->withPivot(['is_active', 'is_starred'])
            ->withTimestamps();
    }
}
