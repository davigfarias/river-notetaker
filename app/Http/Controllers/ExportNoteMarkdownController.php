<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportNoteMarkdownController extends Controller
{
    public function __invoke(Request $request, Notes $note): StreamedResponse
    {
        abort_unless(
            (int) $note->access_token_id === (int) $request->session()->get('access_token_id'),
            403,
        );

        $markdown = $this->toMarkdown($note);

        return response()->streamDownload(
            fn () => print $markdown,
            Str::slug($note->title ?: 'nota').'.md',
            ['Content-Type' => 'text/markdown; charset=utf-8'],
        );
    }

    private function toMarkdown(Notes $note): string
    {
        $note->loadMissing(['concepts', 'pastoral_advice', 'referenceMaterials']);

        $lines = ["# {$note->title}", ''];

        if (filled($note->tags)) {
            $lines[] = collect($note->tags)->map(fn (string $tag): string => "#{$tag}")->implode(' ');
            $lines[] = '';
        }

        if ($note->concepts->isNotEmpty()) {
            $lines[] = '## Conceitos';
            $lines[] = '';
            foreach ($note->concepts as $concept) {
                $lines[] = "- **{$concept->term}**: {$concept->definition}";
            }
            $lines[] = '';
        }

        if ($note->pastoral_advice->isNotEmpty()) {
            $lines[] = '## Conselhos Pastorais';
            $lines[] = '';
            foreach ($note->pastoral_advice as $advice) {
                $lines[] = "- **{$advice->category}**: {$advice->advice}";
            }
            $lines[] = '';
        }

        if (filled($note->impressions)) {
            $lines[] = '## Impressões';
            $lines[] = '';
            $lines[] = $note->impressions;
            $lines[] = '';
        }

        if (filled($note->life_experiences)) {
            $lines[] = '## Experiências de Vida';
            $lines[] = '';
            $lines[] = $note->life_experiences;
            $lines[] = '';
        }

        if ($note->referenceMaterials->isNotEmpty()) {
            $lines[] = '## Referências';
            $lines[] = '';
            foreach ($note->referenceMaterials as $reference) {
                $author = $reference->author ? " — {$reference->author}" : '';
                $year = $reference->year ? " ({$reference->year})" : '';
                $lines[] = "- {$reference->title}{$author}{$year}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
