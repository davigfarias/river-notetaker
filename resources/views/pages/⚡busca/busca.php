<?php

use App\Actions\SearchGlobal;
use App\DTO\SearchResultDTO;
use App\Enums\SearchResultType;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Buscar')] class extends Component
{
    #[Url(as: 'q')]
    public string $q = '';

    /**
     * @return Collection<string, Collection<int, SearchResultDTO>>
     */
    #[Computed]
    public function results(): Collection
    {
        return app(SearchGlobal::class)
            ->handle($this->q, (int) session('access_token_id'), limitPerType: 25)
            ->data
            ->groupBy(fn (SearchResultDTO $result): string => $result->type->value);
    }

    /**
     * @return array<int, SearchResultType>
     */
    #[Computed]
    public function orderedTypes(): array
    {
        return SearchResultType::cases();
    }

    public function updatedQ(): void
    {
        unset($this->results);
    }
};
