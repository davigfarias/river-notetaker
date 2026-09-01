<?php

use App\Actions\AddCitation;
use App\Actions\CreateChapter;
use App\Actions\CreateQuestion;
use App\Actions\DeleteChapter;
use App\Actions\DeleteCitation;
use App\Actions\DeleteQuestion;
use App\Actions\GetReferenceMaterial;
use App\Actions\RefreshClozeBlanks;
use App\Actions\ReorderQuestion;
use App\Actions\RequestExport;
use App\Actions\UpdateChapter;
use App\Actions\UpdateCitation;
use App\Actions\UpdateQuestion;
use App\Actions\UpdateReferenceMaterial;
use App\DTO\ChapterForm;
use App\DTO\CitationForm;
use App\DTO\QuestionForm;
use App\DTO\ReferenceMaterialForm;
use App\Models\Chapter;
use App\Models\Question;
use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Models\ReferenceMaterial;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;
use Flux\Flux;

new #[Title('Obra')] #[Lazy] class extends Component
{
    public int $id;

    public CitationForm $citationForm;

    public CitationForm $editCitationForm;

    public ?int $editingCitationId = null;

    public bool $editingCitation = false;

    public ReferenceMaterialForm $editForm;

    public bool $editingMaterial = false;

    public string $exportFormat = 'docx';

    public ?int $deletingCitationId = null;

    public string $activeTab = 'citacoes';

    public ChapterForm $chapterForm;

    public ChapterForm $editChapterForm;

    public ?int $editingChapterId = null;

    public bool $creatingChapter = false;

    public bool $editingChapter = false;

    public ?int $deletingChapterId = null;

    public QuestionForm $questionForm;

    public QuestionForm $editQuestionForm;

    public ?int $questionChapterId = null;

    public bool $creatingQuestion = false;

    public ?int $editingQuestionId = null;

    public bool $editingQuestion = false;

    public ?int $deletingQuestionId = null;

    public function mount(): void
    {
        abort_if($this->fetch() === null, 404);
    }

    #[Computed]
    public function material(): ?ReferenceMaterial
    {
        return $this->fetch();
    }

    private function fetch(): ?ReferenceMaterial
    {
        return app(GetReferenceMaterial::class)->handle($this->id, (int) session('access_token_id'))->data;
    }

    public function addCitation(AddCitation $action): void
    {
        $this->citationForm->validate();

        $check = $action->handle($this->id, $this->citationForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->citationForm->reset();
            unset($this->material);
        }
    }

    public function editCitation(int $citationId): void
    {
        $citation = $this->material?->citations->firstWhere('id', $citationId);

        if (! $citation) {
            return;
        }

        Flux::modals()->close();

        $this->editingCitationId = $citationId;
        $this->editCitationForm->fillFromModel($citation);
        $this->editingCitation = true;
    }

    public function updateCitation(UpdateCitation $action): void
    {
        $this->editCitationForm->validate();

        $check = $action->handle($this->editingCitationId, $this->editCitationForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->editingCitation = false;
            unset($this->material);
        }
    }

    public function confirmDeleteCitation(int $citationId): void
    {
        $this->deletingCitationId = $citationId;
        $this->modal('delete-citation')->show();
    }

    public function deleteCitation(DeleteCitation $action): void
    {
        if ($this->deletingCitationId === null) {
            return;
        }

        $check = $action->handle($this->deletingCitationId, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('delete-citation')->close();
        $this->deletingCitationId = null;

        unset($this->material);
    }

    public function openEditMaterial(): void
    {
        if (! $this->material) {
            return;
        }

        $this->editForm->fillFromModel($this->material);
        $this->editingMaterial = true;
    }

    public function updateMaterial(UpdateReferenceMaterial $action): void
    {
        $this->editForm->validate();

        $check = $action->handle($this->id, $this->editForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->editingMaterial = false;
            unset($this->material);
        }
    }

    public function export(RequestExport $action): void
    {
        $check = $action->handle(
            scope: ExportScope::Reference,
            format: ExportFormat::from($this->exportFormat),
            accessTokenId: (int) session('access_token_id'),
            referenceMaterialId: $this->id,
        );

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->modal('export')->close();
        }
    }

    private function ownedChapter(int $chapterId): ?Chapter
    {
        return Chapter::query()
            ->where('reference_material_id', $this->id)
            ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', (int) session('access_token_id')))
            ->find($chapterId);
    }

    private function ownedQuestion(int $questionId): ?Question
    {
        return Question::query()
            ->whereHas('chapter', fn ($query) => $query
                ->where('reference_material_id', $this->id)
                ->whereHas('referenceMaterial', fn ($inner) => $inner->where('access_token_id', (int) session('access_token_id'))))
            ->find($questionId);
    }

    private function toast(bool $success, ?string $message): void
    {
        match ($success) {
            true => Flux::toast(text: $message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $message, variant: 'danger'),
        };
    }

    public function openCreateChapter(): void
    {
        $this->chapterForm->reset();
        $this->resetValidation();
        $this->creatingChapter = true;
    }

    public function addChapter(CreateChapter $action): void
    {
        $this->chapterForm->validate();

        if (! $this->material) {
            return;
        }

        $check = $action->handle($this->material, $this->chapterForm->toData());

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->chapterForm->reset();
            $this->creatingChapter = false;
            unset($this->material);
        }
    }

    public function editChapter(int $chapterId): void
    {
        $chapter = $this->ownedChapter($chapterId);

        if (! $chapter) {
            return;
        }

        $this->resetValidation();
        $this->editingChapterId = $chapterId;
        $this->editChapterForm->fillFromChapter($chapter);
        $this->editingChapter = true;
    }

    public function updateChapter(UpdateChapter $action): void
    {
        $this->editChapterForm->validate();

        $chapter = $this->ownedChapter((int) $this->editingChapterId);

        if (! $chapter) {
            return;
        }

        $check = $action->handle($chapter, $this->editChapterForm->toData());

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->editingChapter = false;
            unset($this->material);
        }
    }

    public function confirmDeleteChapter(int $chapterId): void
    {
        $this->deletingChapterId = $chapterId;
        $this->modal('delete-chapter')->show();
    }

    public function deleteChapter(DeleteChapter $action): void
    {
        $chapter = $this->deletingChapterId ? $this->ownedChapter($this->deletingChapterId) : null;

        if ($chapter) {
            $check = $action->handle($chapter);
            $this->toast($check->success, $check->message);
        }

        $this->modal('delete-chapter')->close();
        $this->deletingChapterId = null;
        unset($this->material);
    }

    public function openCreateQuestion(int $chapterId): void
    {
        if (! $this->ownedChapter($chapterId)) {
            return;
        }

        $this->questionForm->reset();
        $this->resetValidation();
        $this->questionChapterId = $chapterId;
        $this->creatingQuestion = true;
    }

    public function addQuestion(CreateQuestion $action, RefreshClozeBlanks $refreshClozeBlanks): void
    {
        $this->questionForm->validate();

        $chapter = $this->ownedChapter((int) $this->questionChapterId);

        if (! $chapter) {
            return;
        }

        $check = $action->handle($chapter, $this->questionForm->toData());

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $refreshClozeBlanks->handle($check->data);
            $this->creatingQuestion = false;
            $this->questionForm->reset();
            unset($this->material);
        }
    }

    public function editQuestion(int $questionId): void
    {
        $question = $this->ownedQuestion($questionId);

        if (! $question) {
            return;
        }

        $this->resetValidation();
        $this->editingQuestionId = $questionId;
        $this->editQuestionForm->fillFromQuestion($question);
        $this->editingQuestion = true;
    }

    public function updateQuestion(UpdateQuestion $action, RefreshClozeBlanks $refreshClozeBlanks): void
    {
        $this->editQuestionForm->validate();

        $question = $this->ownedQuestion((int) $this->editingQuestionId);

        if (! $question) {
            return;
        }

        $check = $action->handle($question, $this->editQuestionForm->toData());

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $refreshClozeBlanks->handle($check->data);
            $this->editingQuestion = false;
            unset($this->material);
        }
    }

    public function confirmDeleteQuestion(int $questionId): void
    {
        $this->deletingQuestionId = $questionId;
        $this->modal('delete-question')->show();
    }

    public function deleteQuestion(DeleteQuestion $action): void
    {
        $question = $this->deletingQuestionId ? $this->ownedQuestion($this->deletingQuestionId) : null;

        if ($question) {
            $check = $action->handle($question);
            $this->toast($check->success, $check->message);
        }

        $this->modal('delete-question')->close();
        $this->deletingQuestionId = null;
        unset($this->material);
    }

    public function moveQuestion(ReorderQuestion $action, int $questionId, int $position): void
    {
        $question = $this->ownedQuestion($questionId);

        if (! $question || $position < 0) {
            return;
        }

        $check = $action->handle($question->chapter, $question, $position);

        if (! $check->success) {
            $this->toast(false, $check->message);
        }

        unset($this->material);
    }
};
