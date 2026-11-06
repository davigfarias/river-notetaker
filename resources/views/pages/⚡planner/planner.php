<?php

use App\Actions\AddPlannerItems;
use App\Actions\DeletePlanner;
use App\Actions\GetPlanner;
use App\Actions\RemovePlannerItem;
use App\Actions\SavePlanner;
use App\Actions\TogglePlannerItemRead;
use App\DTO\PlannerForm;
use App\Models\Planner;
use App\Models\ReferenceMaterial;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Planner')] class extends Component
{
    public int $id;

    public PlannerForm $form;

    public bool $editing = false;

    public bool $adding = false;

    public string $search = '';

    /**
     * Chaves marcadas no seletor: "c12" (capítulo) e "m7" (obra sem capítulos).
     *
     * @var array<int, string>
     */
    public array $selection = [];

    public ?int $removingItemId = null;

    public function mount(): void
    {
        abort_if($this->fetch() === null, 404);
    }

    #[Computed]
    public function planner(): ?Planner
    {
        return $this->fetch();
    }

    private function fetch(): ?Planner
    {
        return app(GetPlanner::class)->handle($this->id, (int) session('access_token_id'))->data;
    }

    /**
     * Itens agrupados por obra, na ordem em que cada obra apareceu primeiro.
     *
     * @return Collection<int, Collection<int, \App\Models\PlannerItem>>
     */
    #[Computed]
    public function groups(): Collection
    {
        return $this->planner->items->groupBy('reference_material_id')->values();
    }

    /**
     * A biblioteca do usuário para o seletor, já filtrada pela busca.
     *
     * @return Collection<int, ReferenceMaterial>
     */
    #[Computed]
    public function library(): Collection
    {
        $term = trim($this->search);

        return ReferenceMaterial::query()
            ->where('access_token_id', (int) session('access_token_id'))
            ->with('chapters:id,reference_material_id,title,position')
            ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('title', 'like', "%{$term}%")
                ->orWhere('author', 'like', "%{$term}%")))
            ->orderBy('title')
            ->get();
    }

    /**
     * Chaves que já estão no planner, para o seletor mostrá-las como "já adicionado".
     *
     * @return array<int, string>
     */
    #[Computed]
    public function taken(): array
    {
        return $this->planner->items
            ->map(fn ($item): string => $item->chapter_id !== null ? "c{$item->chapter_id}" : "m{$item->reference_material_id}")
            ->all();
    }

    public function toggle(int $itemId, TogglePlannerItemRead $action): void
    {
        $check = $action->handle($itemId, (int) session('access_token_id'));

        if (! $check->success) {
            Flux::toast(duration: 2500, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');
        }

        unset($this->planner, $this->groups);
    }

    public function openEdit(): void
    {
        $this->resetValidation();
        $this->form->fillFromModel($this->planner);
        $this->editing = true;
    }

    public function save(SavePlanner $action): void
    {
        $this->form->validate();

        $check = $action->handle($this->form, (int) session('access_token_id'), $this->id);

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->editing = false;
            unset($this->planner);
        }
    }

    public function openAdd(): void
    {
        $this->reset('search', 'selection');
        $this->adding = true;
    }

    public function addItems(AddPlannerItems $action): void
    {
        if ($this->selection === []) {
            Flux::toast(duration: 2500, text: 'Marque pelo menos um capítulo ou obra para adicionar.', variant: 'warning');

            return;
        }

        $check = $action->handle($this->id, $this->selection, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->selection = [];
            unset($this->planner, $this->groups, $this->taken);
        }
    }

    public function confirmRemove(int $itemId): void
    {
        $this->removingItemId = $itemId;
        $this->modal('remove-item')->show();
    }

    public function removeItem(RemovePlannerItem $action): void
    {
        if ($this->removingItemId !== null) {
            $check = $action->handle($this->removingItemId, (int) session('access_token_id'));
            $this->toast($check->success, $check->message);
        }

        $this->modal('remove-item')->close();
        $this->removingItemId = null;
        unset($this->planner, $this->groups, $this->taken);
    }

    public function confirmDelete(): void
    {
        $this->modal('delete-planner')->show();
    }

    public function deletePlanner(DeletePlanner $action): void
    {
        $check = $action->handle($this->id, (int) session('access_token_id'));

        if (! $check->success) {
            $this->toast(false, $check->message);

            return;
        }

        Flux::toast(duration: 2500, text: $check->message, variant: 'success');
        $this->redirectRoute('referencias.planners', navigate: true);
    }

    private function toast(bool $success, ?string $message): void
    {
        match ($success) {
            true => Flux::toast(duration: 2000, text: $message, variant: 'success'),
            false => Flux::toast(duration: 2500, heading: 'Ocorreu um erro', text: $message, variant: 'danger'),
        };
    }
};
