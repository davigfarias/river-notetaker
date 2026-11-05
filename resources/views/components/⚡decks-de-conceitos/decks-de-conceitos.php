<?php

use App\Actions\CreateConceptDeck;
use App\Actions\Orchestrators\BuildConceptStudyStatsOrchestrator;
use App\DTO\ConceptDeckForm;
use App\DTO\ConceptStudyStatsData;
use App\Enums\ConceptActivityRange;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $range = '7d';

    public ConceptDeckForm $deckForm;

    #[Computed]
    public function stats(): ConceptStudyStatsData
    {
        return app(BuildConceptStudyStatsOrchestrator::class)->handle(
            (int) session('access_token_id'),
            ConceptActivityRange::tryFrom($this->range) ?? ConceptActivityRange::Week,
        );
    }

    public function addDeck(CreateConceptDeck $action): void
    {
        $this->deckForm->validate();

        $check = $action->handle($this->deckForm->name, (int) session('access_token_id'));

        if (! $check->success) {
            $this->addError('deckForm.name', $check->message);

            return;
        }

        Flux::toast(duration: 1800, text: $check->message, variant: 'success');
        $this->modal('new-concept-deck')->close();
        $this->deckForm->reset();
        unset($this->stats);
    }
};
