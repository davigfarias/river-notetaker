<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ExportStatus;
use App\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneExports extends Command
{
    protected $signature = 'exports:prune';

    protected $description = 'Delete expired citation export files and mark their records as expired';

    public function handle(): int
    {
        $expired = Export::query()
            ->where('status', ExportStatus::Completed)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $export) {
            if ($export->disk && $export->path && Storage::disk($export->disk)->exists($export->path)) {
                Storage::disk($export->disk)->delete($export->path);
            }

            $export->update([
                'status' => ExportStatus::Expired,
                'path' => null,
                'file_size' => null,
            ]);
        }

        $this->info("{$expired->count()} exportação(ões) expirada(s).");

        return self::SUCCESS;
    }
}
