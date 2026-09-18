<?php

use App\Actions\AddCitation;
use App\Actions\AddReadingNote;
use App\Actions\CreateChapter;
use App\Actions\CreateQuestion;
use App\Actions\DeleteChapter;
use App\Actions\DeleteCitation;
use App\Actions\DeleteQuestion;
use App\Actions\DeleteReadingNote;
use App\Actions\GetReferenceMaterial;
use App\Actions\GetTags;
use App\Actions\PromoteReadingNoteToCitation;
use App\Actions\RefreshClozeBlanks;
use App\Actions\ReorderQuestion;
use App\Actions\RequestExport;
use App\Actions\UpdateChapter;
use App\Actions\UpdateCitation;
use App\Actions\UpdateQuestion;
use App\Actions\UpdateReadingNote;
use App\Actions\UpdateReadingProgress;
use App\Actions\UpdateReferenceMaterial;
use App\Actions\UpdateReferenceTakeaway;
use App\DTO\ChapterForm;
use App\DTO\CitationForm;
use App\DTO\QuestionForm;
use App\DTO\ReadingNoteForm;
use App\DTO\ReferenceMaterialForm;
use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Enums\ReadingStatus;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\ReferenceMaterial;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;

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

    public ?string $readingStatus = null;

    public ?int $currentPage = null;

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

    public ReadingNoteForm $readingNoteForm;

    public ReadingNoteForm $editReadingNoteForm;

    public ?int $editingReadingNoteId = null;

    public bool $editingReadingNote = false;

    public ?int $deletingReadingNoteId = null;

    public ?string $takeaway = null;

    public bool $editingTakeaway = false;

    public function mount(): void
    {
        $material = $this->fetch();

        abort_if($material === null, 404);

        $this->readingStatus = $material->reading_status?->value;
        $this->currentPage = $material->current_page;
        $this->takeaway = $material->notes_takeaway;
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

    #[Computed]
    public function allTags(): Collection
    {
        return collect(app(GetTags::class)->handle()->data ?? []);
    }

    public function toggleReadingNoteTag(string $title): void
    {
        $this->readingNoteForm->toggleTag($title);
    }

    public function toggleEditReadingNoteTag(string $title): void
    {
        $this->editReadingNoteForm->toggleTag($title);
    }

    public function addReadingNote(AddReadingNote $action): void
    {
        $this->readingNoteForm->validate();

        $check = $action->handle($this->id, $this->readingNoteForm, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->readingNoteForm->reset();
            unset($this->material);
        }
    }

    public function editReadingNote(int $readingNoteId): void
    {
        $note = $this->material?->readingNotes->firstWhere('id', $readingNoteId);

        if (! $note) {
            return;
        }

        Flux::modals()->close();

        $this->editingReadingNoteId = $readingNoteId;
        $this->editReadingNoteForm->fillFromModel($note);
        $this->editingReadingNote = true;
    }

    public function updateReadingNote(UpdateReadingNote $action): void
    {
        $this->editReadingNoteForm->validate();

        $check = $action->handle($this->editingReadingNoteId, $this->editReadingNoteForm, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->editingReadingNote = false;
            $this->editingReadingNoteId = null;
            unset($this->material);
        }
    }

    public function confirmDeleteReadingNote(int $readingNoteId): void
    {
        $this->deletingReadingNoteId = $readingNoteId;
        $this->modal('delete-reading-note')->show();
    }

    public function deleteReadingNote(DeleteReadingNote $action): void
    {
        if ($this->deletingReadingNoteId === null) {
            return;
        }

        $check = $action->handle($this->deletingReadingNoteId, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        $this->modal('delete-reading-note')->close();
        $this->deletingReadingNoteId = null;

        unset($this->material);
    }

    public function promoteReadingNote(PromoteReadingNoteToCitation $action, int $readingNoteId): void
    {
        $check = $action->handle($readingNoteId, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            unset($this->material);
        }
    }

    public function saveTakeaway(UpdateReferenceTakeaway $action): void
    {
        $check = $action->handle($this->id, $this->takeaway, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->editingTakeaway = false;
            unset($this->material);
        }
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
            $this->syncReadingState();
        }
    }

    public function updatedReadingStatus(): void
    {
        $this->saveReadingProgress();
    }

    public function updatedCurrentPage(): void
    {
        // Moving the slider implies the book is being read.
        if ($this->readingStatus === null) {
            $this->readingStatus = ReadingStatus::Reading->value;
        }

        $this->saveReadingProgress();
    }

    public function saveReadingProgress(): void
    {
        if (! $this->material?->isTrackable() || $this->readingStatus === null) {
            return;
        }

        $check = app(UpdateReadingProgress::class)->handle(
            $this->id,
            (int) session('access_token_id'),
            ReadingStatus::from($this->readingStatus),
            $this->currentPage,
        );

        if (! $check->success) {
            $this->toast(false, $check->message);

            return;
        }

        unset($this->material);
        $this->syncReadingState();
    }

    private function syncReadingState(): void
    {
        $this->readingStatus = $this->material?->reading_status?->value;
        $this->currentPage = $this->material?->current_page;
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
