<?php

use App\Actions\{AddSoleAdvice, GetPastoralAdvices, ObserveCategory, UpdateAdvice};
use Livewire\Attributes\{Computed, Lazy, Title, Url};
use Illuminate\Pagination\LengthAwarePaginator;
use App\DTO\SoleAdviceDTO;
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Conselhos Pastorais')] #[Lazy] class extends Component
{
    use WithPagination;

    #[Url(as: 'busca')]
    public ?string $search = null;

    public SoleAdviceDTO $formAdvice;

    public SoleAdviceDTO $editAdviceForm;

    public ?int $editingAdviceId = null;

    public bool $editingAdvice = false;

    /** @var array<int, string> */
    public array $categorySuggestions = [];

    public ?string $categorySelectedId = null;

    #[Computed]
    public function themes(): LengthAwarePaginator
    {
        $check = app(GetPastoralAdvices::class)->handle($this->search);

        return $check->data;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function addSoleAdvice(AddSoleAdvice $action): void
    {
        $this->formAdvice->validate();

        $check = $action->handle($this->formAdvice);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('add-advice')->close();

        $this->formAdvice->reset();
        $this->categorySuggestions = [];
        $this->categorySelectedId = null;

        unset($this->themes);
    }

    public function getCategorySuggestions(ObserveCategory $action): void
    {
        // A cada novo foco no campo, destrava o input caso uma sugestão tenha sido selecionada anteriormente.
        $this->categorySelectedId = null;

        $check = $action->handle($this->formAdvice->category);

        $this->categorySuggestions = $check->success ? $check->data->all() : [];
    }

    public function edit(int $id): void
    {
        $advice = collect($this->themes->items())
            ->flatMap(fn (array $theme) => $theme['advices'])
            ->firstWhere('id', $id);

        if (! $advice) {
            return;
        }

        Flux::modals()->close();

        $this->editingAdviceId = $id;
        $this->editAdviceForm->category = $advice->category;
        $this->editAdviceForm->advice = $advice->advice;
        $this->editingAdvice = true;
    }

    public function updateAdvice(UpdateAdvice $action): void
    {
        $this->editAdviceForm->validate();

        $check = $action->handle($this->editingAdviceId, $this->editAdviceForm);

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            unset($this->themes);

            $this->editingAdvice = false;
        }
    }
};
