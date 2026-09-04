<?php

use App\Actions\SearchGlobal;
use App\DTO\SearchResultDTO;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $q = '';

    public bool $show = false;

    /**
     * @return Collection<int, SearchResultDTO>
     */
    #[Computed]
    public function results(): Collection
    {
        return app(SearchGlobal::class)
            ->handle($this->q, (int) session('access_token_id'), limitPerType: 4)
            ->data
            ->take(4);
    }

    public function open(): void
    {
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->q = '';
        unset($this->results);
    }

    public function updatedShow(): void
    {
        if (! $this->show) {
            $this->q = '';
            unset($this->results);
        }
    }
};
