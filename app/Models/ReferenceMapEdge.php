<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $reference_material_id
 * @property string $from_key
 * @property string $to_key
 * @property float $bend
 */
#[Fillable(['reference_material_id', 'from_key', 'to_key', 'bend'])]
#[Table(name: 'reference_map_edges')]
class ReferenceMapEdge extends Model {}
