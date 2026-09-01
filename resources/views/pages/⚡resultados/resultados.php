<?php

use App\Actions\Orchestrators\BuildChapterResultsOrchestrator;
use App\DTO\ChapterResultsData;
use App\Models\AccessToken;
use App\Models\Chapter;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Resultados')] class extends Component
{
    #[Locked]
    public int $chapterId;

    #[Locked]
    public int $referenceMaterialId;

    public function mount(int $id, int $chapterId): void
    {
        $chapter = Chapter::with('referenceMaterial')->findOrFail($chapterId);

        abort_unless($chapter->reference_material_id === $id, 404);
        abort_unless($chapter->referenceMaterial->access_token_id === (int) session('access_token_id'), 404);

        $this->chapterId = $chapter->id;
        $this->referenceMaterialId = $chapter->reference_material_id;
    }

    #[Computed]
    public function chapter(): Chapter
    {
        return Chapter::with('referenceMaterial')->findOrFail($this->chapterId);
    }

    #[Computed]
    public function results(): ChapterResultsData
    {
        $outcome = app(BuildChapterResultsOrchestrator::class)->handle(
            $this->chapter,
            AccessToken::findOrFail(session('access_token_id')),
        );

        if (! $outcome->success) {
            Flux::toast(text: $outcome->message, variant: 'danger');

            return new ChapterResultsData(
                chapterTitle: $this->chapter->title,
                averageScore: 0,
                questionCount: 0,
                rows: [],
            );
        }

        return $outcome->data;
    }
};
