<?php

use App\Actions\BuildPrincipleSessionSummary;
use App\Actions\CountPrinciplesToStudy;
use App\Actions\FindOpenPrincipleSession;
use App\Actions\FinishPrincipleSession;
use App\Actions\Orchestrators\CompleteExerciseReviewOrchestrator;
use App\Actions\Orchestrators\StartPrincipleSessionOrchestrator;
use App\Actions\Orchestrators\SubmitExerciseAnswerOrchestrator;
use App\Actions\PrepareExercise;
use App\Actions\PreviewPrincipleRatingIntervals;
use App\DTO\PreparedExercise;
use App\DTO\PrincipleSessionSummary;
use App\Enums\ExerciseType;
use App\Enums\SelfRating;
use App\Enums\StudyStep;
use App\Models\Disciplines;
use App\Models\PrincipleCategory;
use App\Models\PrincipleSession;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Estudar princípios')] class extends Component
{
    #[Locked]
    public StudyStep $step = StudyStep::Setup;

    #[Locked]
    public ?int $sessionId = null;

    /** Posição do item atual na composição da sessão (= itens já avaliados). */
    #[Locked]
    public int $index = 0;

    #[Locked]
    public ?int $attemptId = null;

    /** Momento (em segundos, com fração) em que a pergunta apareceu, para medir o tempo de resposta. */
    #[Locked]
    public float $shownAt = 0.0;

    public string $answer = '';

    public string $disciplineId = '';

    public string $categoryId = '';

    public bool $includeNotDue = false;

    public function mount(FindOpenPrincipleSession $findOpen): void
    {
        $open = $findOpen->handle($this->tokenId());

        if ($open === null) {
            return;
        }

        $this->sessionId = $open->id;
        $this->index = (int) $open->answered_count;
        $this->showQuestion();
    }

    private function tokenId(): int
    {
        return (int) session('access_token_id');
    }

    private function showQuestion(): void
    {
        $this->step = StudyStep::Question;
        $this->answer = '';
        $this->attemptId = null;
        $this->shownAt = microtime(true);
        unset($this->session, $this->exercise, $this->intervals);
    }

    #[Computed]
    public function session(): ?PrincipleSession
    {
        return $this->sessionId === null ? null : PrincipleSession::query()
            ->where('access_token_id', $this->tokenId())
            ->find($this->sessionId);
    }

    /** @return array{principle_id: int, type: string, retry?: bool}|null */
    #[Computed]
    public function item(): ?array
    {
        return $this->session?->composition[$this->index] ?? null;
    }

    #[Computed]
    public function exercise(): ?PreparedExercise
    {
        $item = $this->item;

        return $item === null ? null : app(PrepareExercise::class)->handle($item['principle_id'], ExerciseType::from($item['type']));
    }

    /** @return array<string, int> */
    #[Computed]
    public function intervals(): array
    {
        $item = $this->item;

        return $item === null ? [] : app(PreviewPrincipleRatingIntervals::class)->handle($this->tokenId(), $item['principle_id'], CarbonImmutable::now());
    }

    #[Computed]
    public function summary(): ?PrincipleSessionSummary
    {
        return $this->sessionId === null ? null : app(BuildPrincipleSessionSummary::class)->handle($this->tokenId(), $this->sessionId);
    }

    #[Computed]
    public function studyCount(): int
    {
        return app(CountPrinciplesToStudy::class)->handle($this->tokenId(), CarbonImmutable::now());
    }

    /** @return Collection<int, Disciplines> */
    #[Computed]
    public function disciplines(): Collection
    {
        return Disciplines::query()->orderBy('title')->get(['id', 'title']);
    }

    /** @return Collection<int, PrincipleCategory> */
    #[Computed]
    public function categories(): Collection
    {
        return PrincipleCategory::query()->with('principleTopic:id,title')->orderBy('title')->get();
    }

    #[Computed]
    public function plannedItems(): int
    {
        return count($this->session->composition ?? []);
    }

    public function start(StartPrincipleSessionOrchestrator $orchestrator): void
    {
        $this->validate([
            'disciplineId' => ['nullable', 'integer', 'exists:disciplines,id'],
            'categoryId' => ['nullable', 'integer', 'exists:principle_categories,id'],
        ], attributes: ['disciplineId' => 'disciplina', 'categoryId' => 'categoria']);

        $this->begin($orchestrator, null);
    }

    /** "Revisar apenas os que errei": nova sessão só com os princípios marcados como Errei. */
    public function reviewMissed(StartPrincipleSessionOrchestrator $orchestrator): void
    {
        if ($this->step !== StudyStep::Summary || $this->summary === null || $this->summary->missedPrincipleIds === []) {
            return;
        }

        $this->begin($orchestrator, $this->summary->missedPrincipleIds);
    }

    /** @param list<int>|null $only */
    private function begin(StartPrincipleSessionOrchestrator $orchestrator, ?array $only): void
    {
        $check = $orchestrator->handle(
            $this->tokenId(),
            $this->disciplineId === '' ? null : (int) $this->disciplineId,
            $this->categoryId === '' ? null : (int) $this->categoryId,
            $this->includeNotDue,
            $only,
        );

        if (! $check->success) {
            Flux::toast(duration: 3000, heading: 'Não foi possível começar', text: $check->message, variant: 'danger');

            return;
        }

        $this->sessionId = $check->data->id;
        $this->index = 0;
        $this->showQuestion();
    }

    /** Grava a resposta (se houver) e mostra o gabarito com os botões de nota. */
    public function reveal(SubmitExerciseAnswerOrchestrator $orchestrator): void
    {
        if ($this->step !== StudyStep::Question || $this->sessionId === null) {
            return;
        }

        $check = $orchestrator->handle(
            $this->tokenId(),
            $this->sessionId,
            $this->index,
            $this->answer,
            (int) round((microtime(true) - $this->shownAt) * 1000),
        );

        if (! $check->success) {
            Flux::toast(duration: 3500, heading: 'Atenção', text: $check->message, variant: 'warning');

            return;
        }

        $this->attemptId = $check->data->id;
        $this->step = StudyStep::Feedback;
    }

    public function rate(string $rating, CompleteExerciseReviewOrchestrator $orchestrator): void
    {
        $selfRating = SelfRating::tryFrom($rating);

        if ($this->step !== StudyStep::Feedback || $this->attemptId === null || $selfRating === null) {
            return;
        }

        $check = $orchestrator->handle($this->tokenId(), $this->attemptId, $selfRating);

        if (! $check->success) {
            Flux::toast(duration: 3500, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        $this->index++;
        unset($this->session);

        if ($check->data['finished']) {
            $this->step = StudyStep::Summary;
            $this->attemptId = null;
            unset($this->summary);

            return;
        }

        $this->showQuestion();
    }

    /** Encerra a sessão agora; o que já foi avaliado fica salvo. */
    public function finishNow(FinishPrincipleSession $finish): void
    {
        if ($this->sessionId === null) {
            return;
        }

        $finish->handle($this->tokenId(), $this->sessionId, CarbonImmutable::now());
        $this->index = (int) ($this->summary?->answered ?? 0);
        $this->step = StudyStep::Summary;
        $this->attemptId = null;
        Flux::modal('leave-study')->close();
        unset($this->summary);
    }

    /** Sai agora e deixa a sessão aberta: ela é retomada na próxima visita. */
    public function leave(): void
    {
        $this->redirectRoute('principios', navigate: true);
    }

    public function newSession(): void
    {
        $this->reset('sessionId', 'attemptId', 'answer');
        $this->index = 0;
        $this->step = StudyStep::Setup;
        unset($this->session, $this->summary, $this->studyCount);
    }
};
