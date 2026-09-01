<?php

use App\Actions\AddReferenceMaterial;
use App\Actions\GetReferenceMaterials;
use App\DTO\ReferenceMaterialForm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public ReferenceMaterialForm $form;

    #[Computed]
    public function materials(): LengthAwarePaginator
    {
        return app(GetReferenceMaterials::class)->handle(
            accessTokenId: (int) session('access_token_id'),
            filter: $this->filter,
            type: $this->type ?: null,
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

    public function addMaterial(AddReferenceMaterial $action): void
    {
        $this->form->validate();

        $check = $action->handle($this->form, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->modal('add-material')->close();
            $this->form->reset();
            unset($this->materials);
        }
    }
};
