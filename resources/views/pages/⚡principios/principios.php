<?php

use App\Actions\CountPrinciplesToStudy;
use App\Actions\CreatePrincipleTopic;
use App\Actions\GetPrincipleTopics;
use App\DTO\PrincipleTopicForm;
use Carbon\CarbonImmutable;
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

    #[Computed]
    public function studyCount(): int
    {
        return app(CountPrinciplesToStudy::class)->handle((int) session('access_token_id'), CarbonImmutable::now());
    }

    public function createTopic(CreatePrincipleTopic $action): void
    {
        $this->form->validate();

        $check = $action->handle($this->form);

        if (! $check->success) {
            Flux::toast(duration: 1800, heading: 'Ocorreu um erro', text: $check->message, variant: 'danger');

            return;
        }

        $this->redirectRoute('principios.show', $check->data->slug, navigate: true);
    }
};
