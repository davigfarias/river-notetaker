<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $discipline_id
 * @property string $from_key
 * @property string $to_key
 * @property float $bend
 */
#[Fillable(['discipline_id', 'from_key', 'to_key', 'bend'])]
#[Table(name: 'map_edges')]
class MapEdge extends Model {}
