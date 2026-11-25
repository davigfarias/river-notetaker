<?php

use App\Enums\NoteType;
use App\Models\Notes;
use App\Support\MindMap;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $noteId;

    public function mount(): void
    {
        $this->note;
    }

    /**
     * Toda ação passa por aqui: nota de outro dono, ou de texto corrido, cai em 404.
     */
    #[Computed]
    public function note(): Notes
    {
        return Notes::query()
            ->where('access_token_id', (int) session('access_token_id'))
            ->where('type', NoteType::MindMap)
            ->findOrFail($this->noteId);
    }

    /**
     * O canvas manda o mapa inteiro a cada mudança (com debounce); limpo antes de gravar.
     *
     * @param  array<string, mixed>  $map
     */
    #[Renderless]
    public function saveMindMap(array $map): void
    {
        $this->note->update(['mind_map' => MindMap::sanitize($map)]);
    }
};
