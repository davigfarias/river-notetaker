<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cada anotação de um livro vira um capítulo (1 para 1), levando texto,
 * degrau, vencimento e histórico de revisão. Só livros: artigos continuam
 * com anotações. Nada é apagado: a anotação antiga fica na tabela, sem uso,
 * e `legacy_reading_note_id` impede copiar duas vezes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $notes = DB::table('reading_notes')
            ->join('reference_materials', 'reference_materials.id', '=', 'reading_notes.reference_material_id')
            ->where('reference_materials.type', 'book-open')
            ->whereNotIn('reading_notes.id', DB::table('chapters')->whereNotNull('legacy_reading_note_id')->select('legacy_reading_note_id'))
            ->orderBy('reading_notes.id')
            ->select('reading_notes.*')
            ->get();

        foreach ($notes as $note) {
            DB::transaction(function () use ($note): void {
                $chapterId = DB::table('chapters')->insertGetId([
                    'reference_material_id' => $note->reference_material_id,
                    'title' => filled($note->title) ? $note->title : Str::limit(Str::squish($note->body), 60),
                    'position' => DB::table('chapters')->where('reference_material_id', $note->reference_material_id)->count(),
                    'body' => $note->body,
                    'location' => $note->location,
                    'tags' => $note->tags,
                    'page_snapshot' => $note->page_snapshot,
                    'review_stage' => $note->review_stage,
                    'next_review_at' => $note->next_review_at,
                    'consolidated_at' => $note->consolidated_at,
                    'dismissed_at' => $note->dismissed_at,
                    'legacy_reading_note_id' => $note->id,
                    'created_at' => $note->created_at,
                    'updated_at' => $note->updated_at,
                ]);

                DB::table('review_logs')
                    ->where('reviewable_type', 'App\\Models\\ReadingNote')
                    ->where('reviewable_id', $note->id)
                    ->update([
                        'reviewable_type' => 'App\\Models\\Chapter',
                        'reviewable_id' => $chapterId,
                    ]);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
