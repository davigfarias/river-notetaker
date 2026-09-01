<?php

use App\Actions\DeleteExport;
use App\Actions\GetExports;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Title('Exportações')] #[Lazy] class extends Component
{
    use WithPagination;

    public ?int $deletingExportId = null;

    #[Computed]
    public function exports(): LengthAwarePaginator
    {
        return app(GetExports::class)->handle((int) session('access_token_id'))->data;
    }

    #[Computed]
    public function hasInProgress(): bool
    {
        return $this->exports->getCollection()
            ->contains(fn ($export): bool => $export->status->isInProgress());
    }

    public function confirmDeleteExport(int $id): void
    {
        $this->deletingExportId = $id;
        $this->modal('delete-export')->show();
    }

    public function deleteExport(DeleteExport $action): void
    {
        if ($this->deletingExportId === null) {
            return;
        }

        $check = $action->handle($this->deletingExportId, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('delete-export')->close();
        $this->deletingExportId = null;

        unset($this->exports);
    }
};
