<?php

use App\Actions\GetPlanners;
use App\Actions\SavePlanner;
use App\DTO\PlannerForm;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Planners')] class extends Component
{
    public PlannerForm $form;

    public bool $creating = false;

    /**
     * @return Collection<int, \App\Models\Planner>
     */
    #[Computed]
    public function planners(): Collection
    {
        return app(GetPlanners::class)->handle((int) session('access_token_id'))->data ?? collect();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->creating = true;
    }

    public function create(SavePlanner $action): void
    {
        $this->form->validate();

        $check = $action->handle($this->form, (int) session('access_token_id'));

        if (! $check->success) {
            Flux::toast(duration: 2500, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        $this->creating = false;
        $this->redirectRoute('referencias.planners.show', ['id' => $check->data->id], navigate: true);
    }
};
