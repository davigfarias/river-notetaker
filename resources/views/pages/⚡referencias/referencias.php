<?php

use App\Actions\AddReferenceMaterial;
use App\Actions\CreateReferenceGroup;
use App\Actions\DeleteReferenceGroup;
use App\Actions\GetReferenceMaterials;
use App\Actions\ToggleReferenceGroupMaterial;
use App\DTO\ReferenceGroupForm;
use App\DTO\ReferenceMaterialForm;
use App\Models\ReferenceGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Title('Referências')] #[Lazy] class extends Component
{
    use WithPagination;

    #[Url(as: 'busca')]
    public ?string $filter = null;

    #[Url(as: 'tipo')]
    public string $type = '';

    #[Url(as: 'grupo')]
    public string $group = '';

    public ReferenceMaterialForm $form;

    public ReferenceGroupForm $groupForm;

    /**
     * @return Collection<int, ReferenceGroup>
     */
    #[Computed]
    public function groups(): Collection
    {
        return ReferenceGroup::query()
            ->where('access_token_id', (int) session('access_token_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Null também quando a URL traz um grupo apagado ou de outro dono: a lista mostra tudo.
     */
    #[Computed]
    public function activeGroup(): ?ReferenceGroup
    {
        return $this->groups->firstWhere('id', (int) $this->group);
    }

    #[Computed]
    public function materials(): LengthAwarePaginator
    {
        return app(GetReferenceMaterials::class)->handle(
            accessTokenId: (int) session('access_token_id'),
            filter: $this->filter,
            type: $this->type ?: null,
            groupId: $this->activeGroup?->id,
        )->data;
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedGroup(): void
    {
        $this->resetPage();
    }

    public function addGroup(CreateReferenceGroup $action): void
    {
        $this->groupForm->validate();

        $check = $action->handle($this->groupForm->name, (int) session('access_token_id'));

        if (! $check->success) {
            $this->addError('groupForm.name', $check->message);

            return;
        }

        Flux::toast(duration: 1800, text: $check->message, variant: 'success');
        $this->modal('add-group')->close();
        $this->groupForm->reset();
        unset($this->groups, $this->activeGroup);
    }

    public function toggleGroup(int $groupId, int $materialId, ToggleReferenceGroupMaterial $action): void
    {
        $check = $action->handle($groupId, $materialId, (int) session('access_token_id'));

        if (! $check->success) {
            Flux::toast(duration: 1800, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');
        }

        unset($this->materials);

        // Tirar a última obra da última página do filtro deixaria uma página vazia.
        if ($this->materials->isEmpty() && $this->materials->currentPage() > 1) {
            $this->resetPage();
            unset($this->materials);
        }
    }

    public function deleteActiveGroup(DeleteReferenceGroup $action): void
    {
        if ($this->activeGroup === null) {
            return;
        }

        $check = $action->handle($this->activeGroup->id, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(duration: 1800, text: $check->message, variant: 'success'),
            false => Flux::toast(duration: 1800, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('delete-group')->close();

        if ($check->success) {
            $this->group = '';
            $this->resetPage();
            unset($this->groups, $this->activeGroup, $this->materials);
        }
    }

    public function addMaterial(AddReferenceMaterial $action): void
    {
        $this->form->validate();

        $check = $action->handle($this->form, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(duration: 1800, text: $check->message, variant: 'success'),
            false => Flux::toast(duration: 1800, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->modal('add-material')->close();
            $this->form->reset();
            unset($this->materials);
        }
    }
};
