<?php

namespace App\Livewire;

use App\Models\Disciplines;
use App\Models\MapEdge;
use App\Models\MapNode;
use App\Models\Notes;
use App\Support\MapConcepts;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mapa de conceitos')] class extends Component
{
    public Disciplines $discipline;

    public function mount(string $slug): void
    {
        $this->discipline = Disciplines::where('slug', $slug)->firstOrFail();
    }

    /**
     * Conceitos marcados com {{no: ...}} nas notas da disciplina.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function concepts(): array
    {
        return MapConcepts::extract(
            Notes::where('discipline_id', $this->discipline->id)->get(['summary', 'impressions', 'life_experiences'])
                ->flatMap(fn ($note) => [$note->summary, $note->impressions, $note->life_experiences])
        );
    }

    /**
     * Nós já postos no canvas cujo marcador ainda existe nas notas.
     *
     * @return list<array{key: string, label: string, x: float, y: float, custom: bool}>
     */
    #[Computed]
    public function nodes(): array
    {
        return MapNode::where('discipline_id', $this->discipline->id)
            ->where(fn ($query) => $query->where('custom', true)->orWhereIn('key', array_keys($this->concepts)))
            ->get()
            ->map(fn (MapNode $node) => [
                'key' => $node->key,
                'label' => $node->custom ? $node->label : $this->concepts[$node->key],
                'x' => $node->x,
                'y' => $node->y,
                'custom' => $node->custom,
            ])
            ->all();
    }

    /**
     * Conceitos ainda não postos no canvas (as pills).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function pills(): array
    {
        return array_diff_key($this->concepts, array_flip(array_column($this->nodes, 'key')));
    }

    /**
     * @return list<array{from: string, to: string, bend: float}>
     */
    #[Computed]
    public function edges(): array
    {
        $keys = array_column($this->nodes, 'key');

        return MapEdge::where('discipline_id', $this->discipline->id)
            ->whereIn('from_key', $keys)
            ->whereIn('to_key', $keys)
            ->get()
            ->map(fn (MapEdge $edge) => ['from' => $edge->from_key, 'to' => $edge->to_key, 'bend' => $edge->bend])
            ->all();
    }

    public function placeNode(string $key, float $x, float $y): void
    {
        $custom = MapNode::where(['discipline_id' => $this->discipline->id, 'key' => $key, 'custom' => true])->first();

        if ($custom) {
            $custom->update(['x' => $x, 'y' => $y]);

            return;
        }

        if (! isset($this->concepts[$key])) {
            return;
        }

        MapNode::updateOrCreate(
            ['discipline_id' => $this->discipline->id, 'key' => $key],
            ['label' => $this->concepts[$key], 'x' => $x, 'y' => $y],
        );

        unset($this->nodes, $this->pills, $this->edges);
    }

    /**
     * Cria um nó livre (sem marcador nas notas) e devolve a chave dele.
     */
    public function addCustomNode(string $label, float $x, float $y): ?string
    {
        $label = Str::limit(trim($label), 60, '');

        if ($label === '') {
            return null;
        }

        $node = MapNode::create([
            'discipline_id' => $this->discipline->id,
            'key' => 'livre:'.Str::lower(Str::random(12)),
            'label' => $label,
            'x' => $x,
            'y' => $y,
            'custom' => true,
        ]);

        unset($this->nodes);

        return $node->key;
    }

    public function renameNode(string $key, string $label): void
    {
        $label = Str::limit(trim($label), 60, '');

        if ($label === '') {
            return;
        }

        MapNode::where(['discipline_id' => $this->discipline->id, 'key' => $key, 'custom' => true])->update(['label' => $label]);

        unset($this->nodes);
    }

    public function removeNode(string $key): void
    {
        MapNode::where('discipline_id', $this->discipline->id)->where('key', $key)->delete();
        MapEdge::where('discipline_id', $this->discipline->id)
            ->where(fn ($query) => $query->where('from_key', $key)->orWhere('to_key', $key))
            ->delete();

        unset($this->nodes, $this->pills, $this->edges);
    }

    public function connect(string $from, string $to): void
    {
        $placed = array_column($this->nodes, 'key');

        if ($from === $to || ! in_array($from, $placed, true) || ! in_array($to, $placed, true)) {
            return;
        }

        MapEdge::firstOrCreate(['discipline_id' => $this->discipline->id, 'from_key' => $from, 'to_key' => $to]);

        unset($this->edges);
    }

    public function disconnect(string $from, string $to): void
    {
        MapEdge::where(['discipline_id' => $this->discipline->id, 'from_key' => $from, 'to_key' => $to])->delete();

        unset($this->edges);
    }

    public function bendEdge(string $from, string $to, float $bend): void
    {
        MapEdge::where(['discipline_id' => $this->discipline->id, 'from_key' => $from, 'to_key' => $to])
            ->update(['bend' => max(-2000, min(2000, $bend))]);

        unset($this->edges);
    }

    /**
     * Inverte a direção; a curvatura muda de sinal pra seta manter o mesmo desenho.
     */
    public function reverseEdge(string $from, string $to): void
    {
        $edge = MapEdge::where(['discipline_id' => $this->discipline->id, 'from_key' => $from, 'to_key' => $to])->first();

        if (! $edge) {
            return;
        }

        $reversed = MapEdge::where(['discipline_id' => $this->discipline->id, 'from_key' => $to, 'to_key' => $from]);

        if ($reversed->exists()) {
            $edge->delete();
        } else {
            $edge->update(['from_key' => $to, 'to_key' => $from, 'bend' => -$edge->bend]);
        }

        unset($this->edges);
    }
};
