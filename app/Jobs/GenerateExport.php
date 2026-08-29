<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExportStatus;
use App\Models\Export;
use App\Support\Export\CitationExporter;
use App\Support\Export\ExportContentResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 85;

    public function __construct(public Export $export) {}

    public function handle(ExportContentResolver $resolver, CitationExporter $exporter): void
    {
        $this->export->update(['status' => ExportStatus::Processing]);

        ['heading' => $heading, 'materials' => $materials] = $resolver->resolve($this->export);

        $contents = $exporter->build($this->export->format, $heading, $materials);

        $disk = config('exports.disk');
        $path = sprintf(
            'exports/%s/%s.%s',
            $this->export->access_token_id ?? 'shared',
            Str::uuid()->toString(),
            $this->export->format->extension(),
        );

        Storage::disk($disk)->put($path, $contents);

        $this->export->update([
            'status' => ExportStatus::Completed,
            'disk' => $disk,
            'path' => $path,
            'filename' => $this->filename($heading),
            'file_size' => strlen($contents),
            'error' => null,
            'expires_at' => now()->addDays((int) config('exports.retention_days')),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->export->update([
            'status' => ExportStatus::Failed,
            'error' => Str::limit((string) $exception?->getMessage(), 1000),
        ]);
    }

    private function filename(string $heading): string
    {
        $slug = Str::slug(Str::limit($heading, 60, ''));

        return trim($slug ?: 'citacoes', '-').'.'.$this->export->format->extension();
    }
}
