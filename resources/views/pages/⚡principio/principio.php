<?php

use App\Actions\AddConceptPrinciple;
use App\Actions\AddTextPrinciple;
use App\Actions\DeletePrinciple;
use App\Actions\GetPrincipleTopic;
use App\Actions\ReorderPrinciple;
use App\Actions\SearchConcept;
use App\Actions\UpdatePrincipleText;
use App\DTO\PrincipleTextForm;
use App\Enums\PrincipleType;
use App\Models\Concepts;
use App\Models\PrincipleTopic;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Princípios')] #[Lazy] class extends Component
{
    public string $slug;

    public string $conceptSearch = '';

    public ?int $selectedConceptId = null;

    public PrincipleTextForm $textForm;

    public bool $addingText = false;

    public ?int $editingPrincipleId = null;

    public ?int $deletingPrincipleId = null;

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        abort_if($this->fetch() === null, 404);
    }

    #[Computed]
    public function topic(): ?PrincipleTopic
    {
        return $this->fetch();
    }

    private function fetch(): ?PrincipleTopic
    {
        return app(GetPrincipleTopic::class)->handle($this->slug)->data;
    }

    /**
     * @return Collection<int, \App\DTO\ConceptsDTO>
     */
    #[Computed]
    public function conceptResults(): Collection
    {
        if (blank($this->conceptSearch)) {
            return collect();
        }

        return app(SearchConcept::class)->handle($this->conceptSearch)->data;
    }

    #[Computed]
    public function selectedConcept(): ?Concepts
    {
        return $this->selectedConceptId ? Concepts::find($this->selectedConceptId) : null;
    }

    public function selectConcept(int $id): void
    {
        $this->selectedConceptId = $id;
    }

    public function addConcept(AddConceptPrinciple $action): void
    {
        if (! $this->selectedConceptId) {
            return;
        }

        $check = $action->handle($this->topic->id, $this->selectedConceptId);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->reset('conceptSearch', 'selectedConceptId');
            $this->modal('add-concept-principle')->close();
            unset($this->topic);
        }
    }

    public function startAddingText(): void
    {
        $this->editingPrincipleId = null;
        $this->textForm->reset();
        $this->addingText = true;
    }

    public function cancelAddingText(): void
    {
        $this->addingText = false;
        $this->textForm->reset();
    }

    public function addText(AddTextPrinciple $action): void
    {
        $this->textForm->validate();

        $check = $action->handle($this->topic->id, $this->textForm);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->textForm->reset();
            $this->addingText = false;
            unset($this->topic);
        }
    }

    public function startEditingText(int $principleId): void
    {
        $principle = $this->topic->principles->firstWhere('id', $principleId);

        if (! $principle || $principle->type !== PrincipleType::Text) {
            return;
        }

        $this->addingText = false;
        $this->textForm->title = $principle->title;
        $this->textForm->body = $principle->body;
        $this->editingPrincipleId = $principleId;
    }

    public function cancelEditingText(): void
    {
        $this->editingPrincipleId = null;
        $this->textForm->reset();
    }

    public function updateText(UpdatePrincipleText $action): void
    {
        $this->textForm->validate();

        $check = $action->handle((int) $this->editingPrincipleId, $this->textForm);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->textForm->reset();
            $this->editingPrincipleId = null;
            unset($this->topic);
        }
    }

    public function movePrinciple(ReorderPrinciple $action, int $principleId, int $position): void
    {
        $principle = $this->topic->principles->firstWhere('id', $principleId);

        if (! $principle || $position < 0) {
            return;
        }

        $check = $action->handle($this->topic, $principle, $position);

        if (! $check->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');
        }

        unset($this->topic);
    }

    public function confirmDeletePrinciple(int $principleId): void
    {
        $this->deletingPrincipleId = $principleId;
        $this->modal('delete-principle')->show();
    }

    public function deletePrinciple(DeletePrinciple $action): void
    {
        if ($this->deletingPrincipleId === null) {
            return;
        }

        $check = $action->handle($this->deletingPrincipleId);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('delete-principle')->close();
        $this->deletingPrincipleId = null;
        unset($this->topic);
    }
};
