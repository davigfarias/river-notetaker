<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $reference_material_id
 * @property string $key
 * @property string $label
 * @property float $x
 * @property float $y
 * @property bool $custom
 */
#[Fillable(['reference_material_id', 'key', 'label', 'x', 'y', 'custom'])]
#[Table(name: 'reference_map_nodes')]
class ReferenceMapNode extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['custom' => 'boolean'];
    }
}
