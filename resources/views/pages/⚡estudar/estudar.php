<?php

use App\Actions\Orchestrators\StartStudySessionOrchestrator;
use App\Actions\Orchestrators\SubmitAnswerOrchestrator;
use App\Actions\Orchestrators\SubmitClozeAnswerOrchestrator;
use App\Actions\RecordQuestionAttempt;
use App\Actions\TokenizeAnswerText;
use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\Question;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Estudar')] class extends Component
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

    public string $answer = '';

    /** @var array<int, string> word index => filled value */
    public array $clozeInputs = [];

    public int $hintLevel = 0;

    public int $sessionRun = 0;

    public function mount(StartStudySessionOrchestrator $orchestrator, int $id, int $chapterId): void
    {
        $chapter = $this->resolveChapter($id, $chapterId);

        $this->startSession($orchestrator, $chapter);
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
        return $this->totalQuestions === 0
            ? 100
            : (int) round(($this->index / $this->totalQuestions) * 100);
    }

    #[Computed]
    public function isClozeQuestion(): bool
    {
        return $this->question !== null
            && $this->question->is_cloze
            && ! empty($this->question->cloze_blank_indices);
    }

    /**
     * The reference answer split for rendering: blank segments carry the word
     * index to bind an input to; text segments carry the visible text.
     *
     * @return array<int, array{blank: bool, index?: int, text?: string}>
     */
    #[Computed]
    public function clozeSegments(): array
    {
        if (! $this->isClozeQuestion()) {
            return [];
        }

        $blankIndices = array_flip($this->question->cloze_blank_indices);
        $tokens = app(TokenizeAnswerText::class)->handle($this->question->reference_answer)->data ?? [];

        $segments = [];

        foreach ($tokens as $token) {
            if ($token['word'] && isset($blankIndices[$token['index']])) {
                $segments[] = ['blank' => true, 'index' => $token['index']];

                continue;
            }

            $segments[] = ['blank' => false, 'text' => $token['text']];
        }

        return $segments;
    }

    /**
     * @return array<int, string>|null null means no hint was requested yet
     */
    public function hintPreview(): ?array
    {
        if ($this->hintLevel === 0 || $this->question === null) {
            return null;
        }

        $keywords = $this->keywordList();

        if ($keywords === []) {
            return [];
        }

        $count = (int) ceil(count($keywords) * min($this->hintLevel, 3) / 3);

        return array_slice($keywords, 0, $count);
    }

    /**
     * @return array<int, string>
     */
    private function keywordList(): array
    {
        if ($this->question === null || blank($this->question->keywords)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->question->keywords))));
    }

    public function revealHint(): void
    {
        if ($this->hintLevel < 3) {
            $this->hintLevel++;
        }
    }

    public function submit(SubmitAnswerOrchestrator $orchestrator, SubmitClozeAnswerOrchestrator $clozeOrchestrator): void
    {
        $question = $this->question;

        if ($question === null) {
            return;
        }

        $accessToken = AccessToken::findOrFail(session('access_token_id'));

        if ($this->isClozeQuestion()) {
            $outcome = $clozeOrchestrator->handle($question, $accessToken, $this->clozeInputs);
        } else {
            $this->validate(['answer' => ['required', 'string']]);

            $outcome = $orchestrator->handle($question, $accessToken, $this->answer);
        }

        if (! $outcome->success) {
            Flux::toast(text: $outcome->message, variant: 'danger');

            return;
        }

        $this->advance();
    }

    public function skip(RecordQuestionAttempt $recordQuestionAttempt): void
    {
        $question = $this->question;

        if ($question === null) {
            return;
        }

        $accessToken = AccessToken::findOrFail(session('access_token_id'));

        $outcome = $recordQuestionAttempt->handle($question, $accessToken, answerText: null, score: null, skipped: true);

        if (! $outcome->success) {
            Flux::toast(text: $outcome->message, variant: 'danger');

            return;
        }

        $this->advance();
    }

    public function restart(StartStudySessionOrchestrator $orchestrator): void
    {
        $this->startSession($orchestrator, $this->resolveChapter($this->referenceMaterialId, $this->chapterId));
    }

    private function resolveChapter(int $referenceMaterialId, int $chapterId): Chapter
    {
        $chapter = Chapter::with('referenceMaterial')->findOrFail($chapterId);

        abort_unless($chapter->reference_material_id === $referenceMaterialId, 404);
        abort_unless($chapter->referenceMaterial->access_token_id === (int) session('access_token_id'), 404);

        return $chapter;
    }

    private function startSession(StartStudySessionOrchestrator $orchestrator, Chapter $chapter): void
    {
        $outcome = $orchestrator->handle($chapter);

        if (! $outcome->success) {
            Flux::toast(text: $outcome->message, variant: 'danger');
            $this->redirect(route('referencias.show', $chapter->reference_material_id), navigate: true);

            return;
        }

        $session = $outcome->data;

        $this->chapterId = $chapter->id;
        $this->referenceMaterialId = $chapter->reference_material_id;
        $this->referenceMaterialTitle = $session->referenceMaterialTitle;
        $this->chapterTitle = $session->chapterTitle;
        $this->questionIds = $session->questionIds;
        $this->totalQuestions = count($session->questionIds);
        $this->index = 0;
        $this->answer = '';
        $this->clozeInputs = [];
        $this->hintLevel = 0;
        $this->sessionRun++;
        unset($this->question, $this->isClozeQuestion, $this->clozeSegments);
    }

    private function advance(): void
    {
        $this->index++;

        if ($this->index >= $this->totalQuestions) {
            $this->redirect(route('referencias.study.results', ['id' => $this->referenceMaterialId, 'chapterId' => $this->chapterId]), navigate: true);

            return;
        }

        $this->answer = '';
        $this->clozeInputs = [];
        $this->hintLevel = 0;
        unset($this->question, $this->isClozeQuestion, $this->clozeSegments);
    }
};
