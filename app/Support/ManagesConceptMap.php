<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Gestos do canvas do mapa de conceitos (resources/js/mapa-conceitos.js), para
 * qualquer dono de mapa: disciplina ou obra.
 */
trait ManagesConceptMap
{
    abstract protected function mapNodes(): HasMany;

    abstract protected function mapEdges(): HasMany;

    /**
     * Textos onde os marcadores {{no: ...}} são procurados.
     *
     * @return iterable<int, string|null>
     */
    abstract protected function conceptTexts(): iterable;

    /**
     * Conceitos marcados com {{no: ...}} nos textos do dono do mapa.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function concepts(): array
    {
        return MapConcepts::extract($this->conceptTexts());
    }

    /**
     * Nós já postos no canvas cujo marcador ainda existe nos textos.
     *
     * @return list<array{key: string, label: string, x: float, y: float, custom: bool}>
     */
    #[Computed]
    public function nodes(): array
    {
        return $this->mapNodes()
            ->where(fn ($query) => $query->where('custom', true)->orWhereIn('key', array_keys($this->concepts)))
            ->get()
            ->map(fn (Model $node) => [
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

        return $this->mapEdges()
            ->whereIn('from_key', $keys)
            ->whereIn('to_key', $keys)
            ->get()
            ->map(fn (Model $edge) => ['from' => $edge->from_key, 'to' => $edge->to_key, 'bend' => $edge->bend])
            ->all();
    }

    public function placeNode(string $key, float $x, float $y): void
    {
        $custom = $this->mapNodes()->where(['key' => $key, 'custom' => true])->first();

        if ($custom) {
            $custom->update(['x' => $x, 'y' => $y]);

            return;
        }

        if (! isset($this->concepts[$key])) {
            return;
        }

        $this->mapNodes()->updateOrCreate(
            ['key' => $key],
            ['label' => $this->concepts[$key], 'x' => $x, 'y' => $y],
        );

        unset($this->nodes, $this->pills, $this->edges);
    }

    /**
     * Cria um nó livre (sem marcador nos textos) e devolve a chave dele.
     */
    public function addCustomNode(string $label, float $x, float $y): ?string
    {
        $label = Str::limit(trim($label), 60, '');

        if ($label === '') {
            return null;
        }

        $node = $this->mapNodes()->create([
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

        $this->mapNodes()->where(['key' => $key, 'custom' => true])->update(['label' => $label]);

        unset($this->nodes);
    }

    public function removeNode(string $key): void
    {
        $this->mapNodes()->where('key', $key)->delete();
        $this->mapEdges()
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

        $this->mapEdges()->firstOrCreate(['from_key' => $from, 'to_key' => $to]);

        unset($this->edges);
    }

    public function disconnect(string $from, string $to): void
    {
        $this->mapEdges()->where(['from_key' => $from, 'to_key' => $to])->delete();

        unset($this->edges);
    }

    public function bendEdge(string $from, string $to, float $bend): void
    {
        $this->mapEdges()->where(['from_key' => $from, 'to_key' => $to])
            ->update(['bend' => max(-2000, min(2000, $bend))]);

        unset($this->edges);
    }

    /**
     * Inverte a direção; a curvatura muda de sinal pra seta manter o mesmo desenho.
     */
    public function reverseEdge(string $from, string $to): void
    {
        $edge = $this->mapEdges()->where(['from_key' => $from, 'to_key' => $to])->first();

        if (! $edge) {
            return;
        }

        if ($this->mapEdges()->where(['from_key' => $to, 'to_key' => $from])->exists()) {
            $edge->delete();
        } else {
            $edge->update(['from_key' => $to, 'to_key' => $from, 'bend' => -$edge->bend]);
        }

        unset($this->edges);
    }
}
