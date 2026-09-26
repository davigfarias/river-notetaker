<?php

namespace App\Models;

use App\Enums\PrincipleType;
use Database\Factories\PrincipleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $principle_topic_id
 * @property int $position
 * @property PrincipleType $type
 * @property int|null $concept_id
 * @property string|null $title
 * @property string|null $body
 */
#[UseFactory(PrincipleFactory::class)]
#[Fillable(['principle_topic_id', 'position', 'type', 'concept_id', 'title', 'body'])]
#[Table(name: 'principles')]
class Principle extends Model
{
    /** @use HasFactory<PrincipleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PrincipleType::class,
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PrincipleTopic, $this>
     */
    public function principleTopic(): BelongsTo
    {
        return $this->belongsTo(PrincipleTopic::class);
    }

    /**
     * @return BelongsTo<Concepts, $this>
     */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concepts::class);
    }

    /**
     * @return HasMany<PrincipleNoteLink, $this>
     */
    public function noteLinks(): HasMany
    {
        return $this->hasMany(PrincipleNoteLink::class);
    }

    protected static function newFactory(): PrincipleFactory
    {
        return PrincipleFactory::new();
    }
}
