<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\SubActions\CreateAdvice;
use App\Actions\SubActions\CreateConcept;
use App\Actions\SubActions\CreateNote;
use App\Actions\SubActions\SyncNoteReferenceMaterials;
use App\DTO\NotesDTO;
use App\Support\Outcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class SaveNote
{
    public function __construct(
        private CreateNote $createNote,
        private CreateConcept $createConcept,
        private CreateAdvice $createAdvice,
        private SyncNoteReferenceMaterials $syncNoteReferenceMaterials,
    ) {}

    public function handle(NotesDTO $data): Outcome
    {
        try {
            DB::beginTransaction();

            $noteOutcome = $this->createNote->handle($data);

            if (! $noteOutcome->success) {
                DB::rollBack();

                return $noteOutcome;
            }

            $note = $noteOutcome->data;

            if ($data->concepts) {
                $this->createConcept->handle($note->id, $data->concepts);
            }

            if ($data->pastoral_advice) {
                $this->createAdvice->handle($note->id, $data->pastoral_advice);
            }

            if ($data->reference_material_ids) {
                $this->syncNoteReferenceMaterials->handle($note->id, $data->reference_material_ids);
            }

            DB::commit();

            return Outcome::success(message: 'Anotação salva com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erro orquestrador SaveNote: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar a anotação.');
        }
    }
}
