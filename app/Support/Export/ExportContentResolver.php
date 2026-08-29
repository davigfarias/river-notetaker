<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Enums\ExportScope;
use App\Models\Citation;
use App\Models\Export;
use App\Models\ReferenceMaterial;
use Illuminate\Support\Collection;

final readonly class ExportContentResolver
{
    /**
     * @return array{heading: string, materials: Collection<int, ReferenceMaterial>}
     */
    public function resolve(Export $export): array
    {
        return match ($export->scope) {
            ExportScope::Reference => $this->forReference($export),
            ExportScope::Search => $this->forSearch($export),
        };
    }

    /**
     * @return array{heading: string, materials: Collection<int, ReferenceMaterial>}
     */
    private function forReference(Export $export): array
    {
        $material = ReferenceMaterial::query()
            ->where('access_token_id', $export->access_token_id)
            ->with(['citations' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($export->reference_material_id);

        return [
            'heading' => 'Citações — '.$material->title,
            'materials' => collect([$material]),
        ];
    }

    /**
     * @return array{heading: string, materials: Collection<int, ReferenceMaterial>}
     */
    private function forSearch(Export $export): array
    {
        $query = (string) $export->search_query;

        $citations = Citation::search($query)
            ->where('access_token_id', $export->access_token_id)
            ->query(fn ($builder) => $builder->with('referenceMaterial'))
            ->get();

        $materials = $citations
            ->groupBy('reference_material_id')
            ->map(function (Collection $group): ReferenceMaterial {
                $material = $group->first()->referenceMaterial;
                $material->setRelation('citations', $group->values());

                return $material;
            })
            ->values();

        return [
            'heading' => 'Citações encontradas para "'.$query.'"',
            'materials' => $materials,
        ];
    }
}
