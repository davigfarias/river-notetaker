<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReferenceGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $access_token_id
 * @property string $name
 */
#[UseFactory(ReferenceGroupFactory::class)]
#[Fillable(['access_token_id', 'name'])]
#[Table(name: 'reference_groups')]
class ReferenceGroup extends Model
{
    /** @use HasFactory<ReferenceGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<ReferenceMaterial, $this>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(ReferenceMaterial::class, 'reference_group_material');
    }
}
