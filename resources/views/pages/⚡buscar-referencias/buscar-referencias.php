<?php

use App\Actions\{RequestExport, SearchCitations, SearchReferenceMaterials};
use Livewire\Attributes\{Computed, Lazy, Title, Url};
use App\Enums\{ExportFormat, ExportScope};
use Livewire\{Component, WithPagination};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Flux\Flux;

new #[Title('Buscar nas referências')] #[Lazy] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public ?string $q = null;

    #[Url(as: 'aba')]
    public string $tab = 'citacoes';

    public string $exportFormat = 'docx';

    #[Computed]
    public function works(): Collection
    {
        return app(SearchReferenceMaterials::class)->handle((string) $this->q, (int) session('access_token_id'))->data;
    }

    #[Computed]
    public function citations(): LengthAwarePaginator
    {
        return app(SearchCitations::class)->handle((string) $this->q, (int) session('access_token_id'))->data;
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function exportSearch(RequestExport $action): void
    {
        $check = $action->handle(
            scope: ExportScope::Search,
            format: ExportFormat::from($this->exportFormat),
            accessTokenId: (int) session('access_token_id'),
            searchQuery: $this->q,
        );

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->modal('export-search')->close();
        }
    }
};
