<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Export;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteExport
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            $export = Export::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($id);

            if ($export->disk && $export->path && Storage::disk($export->disk)->exists($export->path)) {
                Storage::disk($export->disk)->delete($export->path);
            }

            $export->delete();

            return Outcome::success(message: 'Exportação removida.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover exportação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover a exportação.');
        }
    }
}
