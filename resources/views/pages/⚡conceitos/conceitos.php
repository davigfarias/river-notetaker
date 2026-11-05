<?php

use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Conceitos')] class extends Component
{
    #[Url(as: 'aba')]
    public string $aba = '';

    /**
     * Links antigos do Dicionário (?busca=, ?letra=) continuam abrindo o Dicionário;
     * qualquer aba desconhecida cai em Decks.
     */
    public function mount(): void
    {
        if (! in_array($this->aba, ['decks', 'dicionario'], true)) {
            $this->aba = request()->hasAny(['busca', 'letra']) ? 'dicionario' : 'decks';
        }
    }
};
