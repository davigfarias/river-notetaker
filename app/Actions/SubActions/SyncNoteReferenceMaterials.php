<?php

declare(strict_types=1);

namespace App\Actions\SubActions;

use App\Models\Notes;

final readonly class SyncNoteReferenceMaterials
{
    /**
     * @param  array<int, int>  $referenceMaterialIds
     */
    public function handle(int $noteId, array $referenceMaterialIds): void
    {
        $note = Notes::query()->findOrFail($noteId);

        $note->referenceMaterials()->sync($referenceMaterialIds);
    }
}
