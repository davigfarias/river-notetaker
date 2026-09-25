<?php

use App\Actions\CreatePrincipleTopic;
use App\Actions\GetPrincipleTopics;
use App\DTO\PrincipleTopicForm;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Princípios')] #[Lazy] class extends Component
{
    public PrincipleTopicForm $form;

    public bool $addingTopic = false;

    /**
     * @return Collection<int, \App\Models\PrincipleTopic>
     */
    #[Computed]
    public function topics(): Collection
    {
        return app(GetPrincipleTopics::class)->handle()->data;
    }

    public function createTopic(CreatePrincipleTopic $action): void
    {
        $this->form->validate();

        $check = $action->handle($this->form);

        if (! $check->success) {
            Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        $this->redirectRoute('principios.show', $check->data->slug, navigate: true);
    }
};
