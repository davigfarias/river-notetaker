<?php

use App\Actions\GetDisciplines;
use App\Actions\SubActions\{CreateDiscipline, DeleteDiscipline, UpdateDiscipline};
use App\DTO\DisciplinesDTO;
use App\Enums\DisciplineIcon;
use App\Enums\Weekday;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, Title, Url};
use Livewire\Component;
use Livewire\Attributes\Lazy;

new #[Title('Disciplinas')] #[Lazy] class extends Component
{
    /**
     * Valor do filtro de período: vazio para todas, 'sem' para disciplinas sem
     * período definido, ou o número do período.
     */
    #[Url(as: 'periodo')]
    public string $selectedPeriod = '';

    # TODO: refactor this to retire this constants
    public bool $showFormModal = false;
    # TODO: refactor this to retire this constants
    public bool $showDeleteModal = false;

    /**
     * Define se o modal de formulário está criando ou editando uma disciplina.
     */
    public bool $isEditing = false;

    public ?int $disciplineIdToDelete = null;

    public DisciplinesDTO $dto;

    public function mount(DisciplinesDTO $dto): void
    {
        $this->dto = clone $dto;
    }

    public function boot(GetDisciplines $action): void
    {
        $this->disciplines($action);
    }

    #TODO: export this to a Object Form. 
    /**
     * @return array<string, array>
     */
    protected function rules(): array
    {
        return [
            'dto.title' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],
            'dto.icon' => [
                'required',
                'string',
                Rule::in(DisciplineIcon::options()),
            ],
            'dto.period' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            'dto.code' => [
                'nullable',
                'string',
                'max:30',
            ],
            'dto.professor' => [
                'nullable',
                'string',
                'max:100',
            ],
            'dto.class_weekday' => [
                'nullable',
                'integer',
                Rule::enum(Weekday::class),
            ],
            'dto.is_completed' => [
                'boolean',
            ],
        ];
    }

    /**
     * @return string[]
     */
    protected function messages(): array
    {
        return [
            'dto.title' => 'Forneça um título para a sua disciplina',
            'dto.icon' => 'Escolha um ícone na lista',
            'dto.period' => 'O período deve ser um número entre 1 e 12',
            'dto.code' => 'O código deve ter no máximo 30 caracteres',
            'dto.professor' => 'O nome do professor deve ter no máximo 100 caracteres',
            'dto.class_weekday' => 'Escolha um dia da semana válido para a aula',
        ];
    }

    /**
     * @return Collection<int, DisciplinesDTO>
     */
    #[Computed]
    public function disciplines(GetDisciplines $action): void
    {
        $check = $action
            ->handle();

        match ($check->success) {
            true => $this->disciplines = $check->data,
            false => $this->disciplines = collect()
        };
    }

    /**
     * Períodos que possuem ao menos uma disciplina, em ordem crescente.
     *
     * @return Collection<int, int>
     */
    #[Computed]
    public function availablePeriods(): Collection
    {
        return $this->disciplines
            ->pluck('period')
            ->filter(fn (?int $period): bool => $period !== null)
            ->unique()
            ->sort()
            ->values();
    }

    public function hasDisciplinesWithoutPeriod(): bool
    {
        return $this->disciplines
            ->contains(fn (DisciplinesDTO $discipline): bool => $discipline->period === null);
    }

    /**
     * Disciplinas agrupadas por período, já aplicado o filtro selecionado.
     *
     * @return Collection<int, array{period: int|null, label: string, disciplines: Collection<int, DisciplinesDTO>}>
     */
    #[Computed]
    public function groupedDisciplines(): Collection
    {
        return $this->disciplines
            ->filter(fn (DisciplinesDTO $discipline): bool => $this->matchesFilter($discipline))
            ->groupBy(fn (DisciplinesDTO $discipline): string => $discipline->period === null ? 'sem' : (string) $discipline->period)
            ->map(fn (Collection $disciplines, string $key): array => [
                'period' => $key === 'sem' ? null : (int) $key,
                'label' => $key === 'sem' ? 'Sem período' : "{$key}º período",
                'disciplines' => $disciplines->values(),
            ])
            ->sortBy(fn (array $group): int => $group['period'] ?? PHP_INT_MAX)
            ->values();
    }

    private function matchesFilter(DisciplinesDTO $discipline): bool
    {
        return match ($this->selectedPeriod) {
            '' => true,
            'sem' => $discipline->period === null,
            default => $discipline->period === (int) $this->selectedPeriod,
        };
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->dto = new DisciplinesDTO(
            icon: DisciplineIcon::BookOpen->value,
            period: $this->selectedPeriod !== '' && $this->selectedPeriod !== 'sem'
                ? (int) $this->selectedPeriod
                : null,
        );
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $discipline = $this->disciplines
            ->firstWhere('id', $id);

        if (! $discipline instanceof DisciplinesDTO) {
            Flux::toast(heading: 'Ocorreu um erro', text: 'Disciplina não encontrada', variant: 'danger');

            return;
        }

        $this->resetValidation();
        $this->isEditing = true;
        $this->dto = clone $discipline;
        $this->showFormModal = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->disciplineIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function saveDiscipline(
        CreateDiscipline $createDiscipline,
        UpdateDiscipline $updateDiscipline,
        GetDisciplines $getDisciplines,
    ): void {
        $this->validate();

        $check = match ($this->isEditing) {
            true => $updateDiscipline->handle($this->dto),
            false => $createDiscipline->handle($this->dto),
        };

        $heading = $this->isEditing ? 'Disciplina atualizada' : 'Disciplina criada';

        $this->showFormModal = false;

        $this->refreshDisciplines($getDisciplines);

        match ($check->success) {
            true => Flux::toast(heading: $heading, text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };
    }

    public function deleteDiscipline(DeleteDiscipline $action, GetDisciplines $getDisciplines): void
    {
        $check = $action
            ->handle($this->disciplineIdToDelete);

        $this->showDeleteModal = false;

        $this->refreshDisciplines($getDisciplines);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };
    }

    private function refreshDisciplines(GetDisciplines $getDisciplines): void
    {
        $this->disciplines($getDisciplines);

        unset($this->availablePeriods, $this->groupedDisciplines);
    }
};
