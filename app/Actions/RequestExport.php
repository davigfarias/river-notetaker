<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Enums\ExportStatus;
use App\Jobs\GenerateExport;
use App\Models\Export;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RequestExport
{
    public function handle(
        ExportScope $scope,
        ExportFormat $format,
        int $accessTokenId,
        ?int $referenceMaterialId = null,
        ?string $searchQuery = null,
    ): Outcome {
        try {
            if ($scope === ExportScope::Reference) {
                $material = ReferenceMaterial::query()
                    ->where('access_token_id', $accessTokenId)
                    ->withCount('citations')
                    ->find($referenceMaterialId);

                if (! $material) {
                    return Outcome::failure(message: 'Obra não encontrada.');
                }

                if ($material->citations_count === 0) {
                    return Outcome::failure(message: 'Esta obra ainda não tem citações para exportar.');
                }
            }

            if ($scope === ExportScope::Search && blank($searchQuery)) {
                return Outcome::failure(message: 'Informe um termo de busca para exportar.');
            }

            $export = Export::create([
                'access_token_id' => $accessTokenId,
                'format' => $format,
                'scope' => $scope,
                'reference_material_id' => $scope === ExportScope::Reference ? $referenceMaterialId : null,
                'search_query' => $scope === ExportScope::Search ? $searchQuery : null,
                'status' => ExportStatus::Pending,
                'filename' => 'citacoes.'.$format->extension(),
            ]);

            GenerateExport::dispatch($export);

            return Outcome::success(message: 'Exportação iniciada. Acompanhe em "Exportações".', data: $export);
        } catch (\Exception $e) {
            Log::error("Erro ao solicitar exportação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar a exportação.');
        }
    }
}
