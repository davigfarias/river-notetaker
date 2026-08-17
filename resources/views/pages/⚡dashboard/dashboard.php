<?php

use App\Actions\GetDisciplines;
use App\Actions\SubActions\CreateDiscipline;
use App\Actions\SubActions\DeleteDiscipline;
use App\DTO\DisciplinesDTO;
use App\Enums\DisciplineIcon;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    public bool $showCreateModal = false;

    public bool $showDeleteModal = false;

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
            false => $this->disciplines = null
        };
    }

    public function openCreateModal(): void
    {
        $this->dto->title = '';
        $this->dto->icon = DisciplineIcon::BookOpen->value;
        $this->showCreateModal = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->disciplineIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function addDiscipline(CreateDiscipline $createDiscipline, GetDisciplines $getDisciplines): void
    {
        $this->validate();

        $check = $createDiscipline
            ->handle($this->dto->title, $this->dto->icon);

        $this->showCreateModal = false;

        $this->disciplines($getDisciplines);

        match ($check->success) {
            true => Flux::toast(heading: 'Disciplina criada', text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };
    }

    public function deleteDiscipline(DeleteDiscipline $action, GetDisciplines $getDisciplines): void
    {
        $check = $action
            ->handle($this->disciplineIdToDelete);

        $this->showDeleteModal = false;

        $this->disciplines($getDisciplines);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };
    }
};
