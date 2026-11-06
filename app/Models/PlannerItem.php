<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlannerItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $planner_id
 * @property int $reference_material_id
 * @property int|null $chapter_id
 * @property int $position
 * @property Carbon|null $read_at
 */
#[UseFactory(PlannerItemFactory::class)]
#[Fillable(['planner_id', 'reference_material_id', 'chapter_id', 'position', 'read_at'])]
#[Table(name: 'planner_items')]
class PlannerItem extends Model
{
    /** @use HasFactory<PlannerItemFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'position' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Planner, $this>
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(Planner::class);
    }

    /**
     * @return BelongsTo<ReferenceMaterial, $this>
     */
    public function referenceMaterial(): BelongsTo
    {
        return $this->belongsTo(ReferenceMaterial::class);
    }

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * O que aparece na linha: o capítulo ou, sem capítulos, a própria obra.
     */
    public function label(): string
    {
        return $this->chapter?->title ?? $this->referenceMaterial->title;
    }
}
