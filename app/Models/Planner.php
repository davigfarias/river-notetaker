<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $access_token_id
 * @property string $name
 * @property string|null $objective
 * @property Carbon|null $target_date
 * @property int|null $items_count
 * @property int|null $read_items_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(PlannerFactory::class)]
#[Fillable(['access_token_id', 'name', 'objective', 'target_date'])]
#[Table(name: 'planners')]
class Planner extends Model
{
    /** @use HasFactory<PlannerFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    /**
     * @return HasMany<PlannerItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PlannerItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Itens lidos e total. Usa as contagens carregadas com `withProgress()`
     * quando existem; senão conta no banco.
     *
     * @return array{read: int, total: int, percent: int}
     */
    public function progress(): array
    {
        $total = $this->items_count ?? $this->items()->count();
        $read = $this->read_items_count ?? $this->items()->whereNotNull('read_at')->count();

        return [
            'read' => $read,
            'total' => $total,
            'percent' => $total === 0 ? 0 : (int) round($read / $total * 100),
        ];
    }

    /**
     * @param  Builder<Planner>  $query
     * @return Builder<Planner>
     */
    public function scopeWithProgress($query)
    {
        return $query->withCount([
            'items',
            'items as read_items_count' => fn ($items) => $items->whereNotNull('read_at'),
        ]);
    }
}
