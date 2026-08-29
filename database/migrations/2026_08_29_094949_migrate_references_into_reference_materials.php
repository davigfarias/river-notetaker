<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Moves every legacy `references` row (a free-text reference bound to a
     * single note) into a `reference_materials` record and rewires the
     * association through the `note_reference_material` pivot.
     */
    public function up(): void
    {
        if (! Schema::hasTable('references')) {
            return;
        }

        $now = now();

        DB::table('references')->orderBy('id')->each(function (object $reference) use ($now): void {
            $accessTokenId = DB::table('notes')
                ->where('id', $reference->note_id)
                ->value('access_token_id');

            $materialId = DB::table('reference_materials')->insertGetId([
                'access_token_id' => $accessTokenId,
                'title' => $reference->reference_text,
                'type' => $reference->type,
                'created_at' => $reference->created_at ?? $now,
                'updated_at' => $reference->updated_at ?? $now,
            ]);

            DB::table('note_reference_material')->insert([
                'note_id' => $reference->note_id,
                'reference_material_id' => $materialId,
                'created_at' => $reference->created_at ?? $now,
                'updated_at' => $reference->updated_at ?? $now,
            ]);
        });

        Schema::drop('references');
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the `references` table and restores rows from the pivot. Any
     * reference material metadata added after the forward migration is lost.
     */
    public function down(): void
    {
        if (Schema::hasTable('references')) {
            return;
        }

        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes');
            $table->string('type');
            $table->string('reference_text');
            $table->timestamps();
        });

        DB::table('note_reference_material')
            ->join('reference_materials', 'reference_materials.id', '=', 'note_reference_material.reference_material_id')
            ->orderBy('note_reference_material.id')
            ->get([
                'note_reference_material.note_id',
                'reference_materials.title',
                'reference_materials.type',
                'note_reference_material.created_at',
                'note_reference_material.updated_at',
            ])
            ->each(function (object $row): void {
                DB::table('references')->insert([
                    'note_id' => $row->note_id,
                    'type' => $row->type,
                    'reference_text' => $row->title,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            });
    }
};
