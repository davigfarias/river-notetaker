<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('concept_decks')) {
            Schema::create('concept_decks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens')->cascadeOnDelete();
                $table->string('name', 60);
                $table->timestamps();

                $table->unique(['access_token_id', 'name'], 'concept_decks_token_name_unique');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
