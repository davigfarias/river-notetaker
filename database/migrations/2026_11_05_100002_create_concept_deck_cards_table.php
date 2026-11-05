<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('concept_deck_cards')) {
            Schema::create('concept_deck_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('concept_deck_id')->constrained('concept_decks', indexName: 'cdc_deck_foreign')->cascadeOnDelete();
                $table->foreignId('concept_id')->constrained('concepts', indexName: 'cdc_concept_foreign')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_starred')->default(false);
                $table->timestamps();

                $table->unique(['concept_deck_id', 'concept_id'], 'cdc_deck_concept_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
