<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $reference_material_id
 * @property int|null $access_token_id
 * @property string $quote_text
 * @property string|null $location
 * @property string|null $personal_note
 */
#[UseFactory(CitationFactory::class)]
#[Fillable([
    'reference_material_id',
    'access_token_id',
    'quote_text',
    'location',
    'personal_note',
])]
#[Table(name: 'citations')]
class Citation extends Model
{
    /** @use HasFactory<CitationFactory> */
    use HasFactory, Searchable;

    /**
     * @return BelongsTo<ReferenceMaterial, $this>
     */
    public function referenceMaterial(): BelongsTo
    {
        return $this->belongsTo(ReferenceMaterial::class);
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return array{
     *     quote_text: string,
     *     location: string|null,
     *     personal_note: string|null
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'quote_text' => $this->quote_text,
            'location' => $this->location,
            'personal_note' => $this->personal_note,
        ];
    }
}
