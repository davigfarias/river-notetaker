<?php

namespace App\Livewire;

use App\Models\Disciplines;
use App\Models\Notes;
use App\Support\ManagesConceptMap;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mapa de conceitos')] class extends Component
{
    use ManagesConceptMap;

    public Disciplines $discipline;

    public function mount(string $slug): void
    {
        $this->discipline = Disciplines::where('slug', $slug)->firstOrFail();
    }

    protected function mapNodes(): HasMany
    {
        return $this->discipline->mapNodes();
    }

    protected function mapEdges(): HasMany
    {
        return $this->discipline->mapEdges();
    }

    /**
     * @return iterable<int, string|null>
     */
    protected function conceptTexts(): iterable
    {
        return Notes::where('discipline_id', $this->discipline->id)->get(['summary', 'impressions', 'life_experiences'])
            ->flatMap(fn ($note) => [$note->summary, $note->impressions, $note->life_experiences]);
    }
};
