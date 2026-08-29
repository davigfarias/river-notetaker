<?php

use App\Actions\DeleteExport;
use App\Actions\GetExports;
use App\Enums\ExportStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Title('Exportações')] class extends Component
{
    use WithPagination;

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

    public function deleteExport(int $id, DeleteExport $action): void
    {
        $check = $action->handle($id, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        unset($this->exports);
    }
};
