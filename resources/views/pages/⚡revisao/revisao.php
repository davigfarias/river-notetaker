<?php

use App\Actions\FindQuestionsForChapter;
use App\Models\Chapter;
use App\Models\Question;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Revisão')] class extends Component
{
    #[Locked]
    public int $chapterId;

    #[Locked]
    public int $referenceMaterialId;

    public string $referenceMaterialTitle = '';

    public string $chapterTitle = '';

    /** @var array<int, int> */
    #[Locked]
    public array $questionIds = [];

    #[Locked]
    public int $totalQuestions = 0;

    public int $index = 0;

    public function mount(FindQuestionsForChapter $findQuestionsForChapter, int $id, int $chapterId): void
    {
        $chapter = Chapter::with('referenceMaterial')->findOrFail($chapterId);

        abort_unless($chapter->reference_material_id === $id, 404);
        abort_unless($chapter->referenceMaterial->access_token_id === (int) session('access_token_id'), 404);

        $outcome = $findQuestionsForChapter->handle($chapter);

        if (! $outcome->success) {
            Flux::toast(text: $outcome->message, variant: 'danger');
            $this->redirect(route('referencias.show', $chapter->reference_material_id), navigate: true);

            return;
        }

        $this->chapterId = $chapter->id;
        $this->referenceMaterialId = $chapter->reference_material_id;
        $this->referenceMaterialTitle = $chapter->referenceMaterial->title;
        $this->chapterTitle = $chapter->title;
        $this->questionIds = $outcome->data->pluck('id')->all();
        $this->totalQuestions = count($this->questionIds);
    }

    #[Computed]
    public function question(): ?Question
    {
        $id = $this->questionIds[$this->index] ?? null;

        return $id ? Question::find($id) : null;
    }

    #[Computed]
    public function progress(): int
    {
        return $this->totalQuestions <= 1
            ? 100
            : (int) round(($this->index / ($this->totalQuestions - 1)) * 100);
    }

    public function previous(): void
    {
        if ($this->index > 0) {
            $this->index--;
            unset($this->question);
        }
    }

    public function next(): void
    {
        if ($this->index < $this->totalQuestions - 1) {
            $this->index++;
            unset($this->question);
        }
    }
};
