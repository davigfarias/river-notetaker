<?php

use App\Actions\{AddHistoricalEvent, DeleteHistoricalEvent, GetHistoricalEvents, UpdateHistoricalEvent};
use Livewire\Attributes\{Computed, Lazy, Title, Url};
use Illuminate\Database\Eloquent\Collection;
use App\DTO\HistoricalEventForm;
use App\Models\HistoricalEvent;
use Livewire\Component;
use Flux\Flux;

new #[Title('Histórico')] #[Lazy] class extends Component
{
    #[Url(as: 'busca')]
    public ?string $search = null;

    public HistoricalEventForm $eventForm;

    public HistoricalEventForm $editEventForm;

    public ?int $editingEventId = null;

    public bool $editingEvent = false;

    public ?int $deletingEventId = null;

    /**
     * @return Collection<int, HistoricalEvent>
     */
    #[Computed]
    public function events(): Collection
    {
        return app(GetHistoricalEvents::class)->handle((int) session('access_token_id'), $this->search)->data;
    }

    public function addEvent(AddHistoricalEvent $action): void
    {
        $this->eventForm->validate();

        $check = $action->handle($this->eventForm, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->modal('add-event')->close();
            $this->eventForm->reset();
            unset($this->events);
        }
    }

    public function editEvent(int $eventId): void
    {
        $event = $this->events->firstWhere('id', $eventId);

        if (! $event) {
            return;
        }

        $this->editingEventId = $eventId;
        $this->editEventForm->resetValidation();
        $this->editEventForm->fillFromModel($event);
        $this->editingEvent = true;
    }

    public function updateEvent(UpdateHistoricalEvent $action): void
    {
        if ($this->editingEventId === null) {
            return;
        }

        $this->editEventForm->validate();

        $check = $action->handle($this->editingEventId, $this->editEventForm, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        if ($check->success) {
            $this->editingEvent = false;
            $this->editingEventId = null;
            unset($this->events);
        }
    }

    public function confirmDeleteEvent(int $eventId): void
    {
        $this->deletingEventId = $eventId;
        $this->modal('delete-event')->show();
    }

    public function deleteEvent(DeleteHistoricalEvent $action): void
    {
        if ($this->deletingEventId === null) {
            return;
        }

        $check = $action->handle($this->deletingEventId, (int) session('access_token_id'));

        $this->toast($check->success, $check->message);

        $this->modal('delete-event')->close();
        $this->deletingEventId = null;

        unset($this->events);
    }

    private function toast(bool $success, ?string $message): void
    {
        match ($success) {
            true => Flux::toast(text: $message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $message, variant: 'danger'),
        };
    }
};
