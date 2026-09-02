<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Summarizer;
use App\Models\Notes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateNoteSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout;

    public function __construct(public Notes $note)
    {
        $this->timeout = (int) config('summarizer.job_timeout', 60);
    }

    public function handle(): void
    {
        $this->note->loadMissing(['concepts', 'pastoral_advice']);

        $summary = (new Summarizer)
            ->prompt($this->buildContentForAgent())
            ->text;

        $this->note->update(['ai_summary' => trim($summary)]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("Erro ao gerar resumo da nota {$this->note->id}: {$exception?->getMessage()}");
    }

    private function buildContentForAgent(): string
    {
        $sections = [];

        $sections[] = "Título: {$this->note->title}";

        $concepts = $this->note->concepts
            ->map(fn ($concept): string => "- {$concept->term}: {$concept->definition}")
            ->implode("\n");

        if (filled($concepts)) {
            $sections[] = "Conceitos:\n{$concepts}";
        }

        $advice = $this->note->pastoral_advice
            ->map(fn ($advice): string => "- {$advice->category}: {$advice->advice}")
            ->implode("\n");

        if (filled($advice)) {
            $sections[] = "Conselhos pastorais:\n{$advice}";
        }

        if (filled($this->note->impressions)) {
            $sections[] = "Impressões:\n{$this->note->impressions}";
        }

        if (filled($this->note->life_experiences)) {
            $sections[] = "Experiências de vida:\n{$this->note->life_experiences}";
        }

        return implode("\n\n", $sections);
    }
}
