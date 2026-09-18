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

        $summarizer = new Summarizer;

        $summary = trim($summarizer->prompt($this->buildContentForAgent())->text);

        $this->note->update(['ai_summary' => $this->withinLimit($summarizer, $summary)]);
    }

    /**
     * Devolve o resumo dentro do teto tolerado.
     *
     * Modelos de linguagem não contam caracteres com precisão, então um
     * estouro é esperado. Numa primeira falha pedimos que encurtem; numa
     * segunda, aceitamos o texto mais curto entre as duas tentativas — perder
     * o resumo seria pior que guardar um longo demais para a locução.
     */
    private function withinLimit(Summarizer $summarizer, string $summary): string
    {
        $limit = (int) config('summarizer.max_characters');

        if (mb_strlen($summary) <= $limit) {
            return $summary;
        }

        $target = (int) config('summarizer.target_characters');

        $shortened = trim($summarizer->prompt(
            "Encurte o texto a seguir para no máximo {$target} caracteres, mantendo a tese "
            .'central e as mesmas restrições de locução. Responda apenas com o texto encurtado.'
            ."\n\n{$summary}"
        )->text);

        return mb_strlen($shortened) < mb_strlen($summary) ? $shortened : $summary;
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
