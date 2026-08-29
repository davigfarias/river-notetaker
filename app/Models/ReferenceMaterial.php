<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferencesIcon;
use Database\Factories\ReferenceMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int|null $access_token_id
 * @property string $title
 * @property string|null $author
 * @property int|null $year
 * @property string $type
 * @property string|null $publisher
 * @property string|null $url
 * @property string|null $abnt_reference
 */
#[UseFactory(ReferenceMaterialFactory::class)]
#[Fillable([
    'access_token_id',
    'title',
    'author',
    'year',
    'type',
    'publisher',
    'url',
    'abnt_reference',
])]
#[Table(name: 'reference_materials')]
class ReferenceMaterial extends Model
{
    /** @use HasFactory<ReferenceMaterialFactory> */
    use HasFactory, Searchable;

    public function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return HasMany<Citation, $this>
     */
    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    /**
     * @return BelongsToMany<Notes, $this>
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(
            Notes::class,
            'note_reference_material',
            'reference_material_id',
            'note_id',
        );
    }

    public function typeIcon(): ReferencesIcon
    {
        return ReferencesIcon::tryFrom($this->type) ?? ReferencesIcon::BookOpen;
    }

    /**
     * @return array{
     *     title: string,
     *     author: string|null,
     *     abnt_reference: string|null
     * }
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'author' => $this->author,
            'abnt_reference' => $this->abnt_reference,
        ];
    }
}
