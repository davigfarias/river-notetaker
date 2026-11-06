<?php

use App\Models\ReferenceMaterial;
use App\Support\ManagesConceptMap;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ManagesConceptMap;

    #[Locked]
    public int $referenceMaterialId;

    public function mount(): void
    {
        $this->material;
    }

    /**
     * Toda ação passa por aqui: obra de outro dono cai em 404.
     */
    #[Computed]
    public function material(): ReferenceMaterial
    {
        return ReferenceMaterial::query()
            ->where('access_token_id', (int) session('access_token_id'))
            ->findOrFail($this->referenceMaterialId);
    }

    protected function mapNodes(): HasMany
    {
        return $this->material->mapNodes();
    }

    protected function mapEdges(): HasMany
    {
        return $this->material->mapEdges();
    }

    /**
     * Só as anotações da obra (capítulos, nos livros); a mais antiga define o rótulo de marcadores repetidos.
     *
     * @return iterable<int, string|null>
     */
    protected function conceptTexts(): iterable
    {
        return $this->material->isBook()
            ? $this->material->chapters()->whereNotNull('body')->reorder('id')->pluck('body')
            : $this->material->readingNotes()->reorder('id')->pluck('body');
    }
};
