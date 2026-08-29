<?php

use App\Actions\AddCitation;
use App\Actions\DeleteCitation;
use App\Actions\GetReferenceMaterial;
use App\Actions\RequestExport;
use App\Actions\UpdateCitation;
use App\Actions\UpdateReferenceMaterial;
use App\DTO\CitationForm;
use App\DTO\ReferenceMaterialForm;
use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Models\ReferenceMaterial;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Flux\Flux;

new #[Title('Obra')] class extends Component
{
    public int $id;

    public CitationForm $citationForm;

    public CitationForm $editCitationForm;

    public ?int $editingCitationId = null;

    public bool $editingCitation = false;

    public ReferenceMaterialForm $editForm;

    public bool $editingMaterial = false;

    public string $exportFormat = 'docx';

    public bool $ready = false;

    public ?int $deletingCitationId = null;

    public function mount(): void
    {
        abort_if($this->fetch() === null, 404);
    }

    public function loadContent(): void
    {
        $this->ready = true;
    }

    #[Computed]
    public function material(): ?ReferenceMaterial
    {
        return $this->fetch();
    }

    private function fetch(): ?ReferenceMaterial
    {
        return app(GetReferenceMaterial::class)->handle($this->id, (int) session('access_token_id'))->data;
    }

    public function addCitation(AddCitation $action): void
    {
        $this->citationForm->validate();

        $check = $action->handle($this->id, $this->citationForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->citationForm->reset();
            unset($this->material);
        }
    }

    public function editCitation(int $citationId): void
    {
        $citation = $this->material?->citations->firstWhere('id', $citationId);

        if (! $citation) {
            return;
        }

        Flux::modals()->close();

        $this->editingCitationId = $citationId;
        $this->editCitationForm->fillFromModel($citation);
        $this->editingCitation = true;
    }

    public function updateCitation(UpdateCitation $action): void
    {
        $this->editCitationForm->validate();

        $check = $action->handle($this->editingCitationId, $this->editCitationForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->editingCitation = false;
            unset($this->material);
        }
    }

    public function confirmDeleteCitation(int $citationId): void
    {
        $this->deletingCitationId = $citationId;
        $this->modal('delete-citation')->show();
    }

    public function deleteCitation(DeleteCitation $action): void
    {
        if ($this->deletingCitationId === null) {
            return;
        }

        $check = $action->handle($this->deletingCitationId, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        $this->modal('delete-citation')->close();
        $this->deletingCitationId = null;

        unset($this->material);
    }

    public function openEditMaterial(): void
    {
        if (! $this->material) {
            return;
        }

        $this->editForm->fillFromModel($this->material);
        $this->editingMaterial = true;
    }

    public function updateMaterial(UpdateReferenceMaterial $action): void
    {
        $this->editForm->validate();

        $check = $action->handle($this->id, $this->editForm, (int) session('access_token_id'));

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->editingMaterial = false;
            unset($this->material);
        }
    }

    public function export(RequestExport $action): void
    {
        $check = $action->handle(
            scope: ExportScope::Reference,
            format: ExportFormat::from($this->exportFormat),
            accessTokenId: (int) session('access_token_id'),
            referenceMaterialId: $this->id,
        );

        match ($check->success) {
            true => Flux::toast(text: $check->message, variant: 'success'),
            false => Flux::toast(heading: 'Ocorreu um erro', text: $check->message, variant: 'danger'),
        };

        if ($check->success) {
            $this->modal('export')->close();
        }
    }
};
