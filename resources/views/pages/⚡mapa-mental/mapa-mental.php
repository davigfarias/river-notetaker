<?php

namespace App\Livewire;

use App\Models\Disciplines;
use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Mapa mental')] class extends Component
{
    /**
     * Tipos de diagrama aceitos. O conteúdo é só o fonte mermaid (sem cerca
     * ```), e a página só o exibe como diagrama, nunca como Markdown.
     */
    public const DIAGRAM_PATTERN = '/\A\s*(mindmap|flowchart|graph)\b/';

    public Disciplines $discipline;

    public string $mindMap = '';

    public bool $editing = false;

    public function mount(string $slug): void
    {
        $this->discipline = Disciplines::where('slug', $slug)->firstOrFail();
        $this->mindMap = (string) $this->discipline->mind_map;
        $this->editing = $this->mindMap === '';
    }

    public function save(): void
    {
        $this->validate([
            'mindMap' => [
                'required',
                'string',
                'max:20000',
                'not_regex:/```/',
                'regex:'.self::DIAGRAM_PATTERN,
            ],
        ], [
            'mindMap.required' => 'Escreva o diagrama mermaid.',
            'mindMap.not_regex' => 'Só o código mermaid, sem a cerca ```.',
            'mindMap.regex' => 'Deve começar com mindmap, flowchart ou graph.',
        ]);

        $this->discipline->update(['mind_map' => trim($this->mindMap)]);
        $this->editing = false;

        Flux::toast(duration: 1800, text: 'Mapa mental salvo!', variant: 'success');
    }

    public function startEditing(): void
    {
        $this->mindMap = (string) $this->discipline->mind_map ?: "mindmap\n  root(({$this->discipline->title}))\n    Tópico 1\n    Tópico 2\n";
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->mindMap = (string) $this->discipline->mind_map;
        $this->editing = false;
    }
};
