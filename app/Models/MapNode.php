<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $discipline_id
 * @property string $key
 * @property string $label
 * @property float $x
 * @property float $y
 * @property bool $custom
 */
#[Fillable(['discipline_id', 'key', 'label', 'x', 'y', 'custom'])]
#[Table(name: 'map_nodes')]
class MapNode extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['custom' => 'boolean'];
    }
}
