<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('principle_exercises')) {
            Schema::create('principle_exercises', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_token_id')->constrained('access_tokens', indexName: 'pex_token_foreign')->cascadeOnDelete();
                $table->foreignId('principle_id')->constrained('principles', indexName: 'pex_principle_foreign')->cascadeOnDelete();
                $table->string('type');
                $table->string('bloom_level');
                $table->json('payload');
                $table->string('source_hash', 64);
                $table->string('prompt_version');
                $table->string('model')->nullable();
                $table->string('status')->default('ready');
                $table->unsignedInteger('times_served')->default(0);
                $table->dateTime('last_served_at')->nullable();
                $table->dateTime('generated_at');
                $table->timestamps();

                $table->index(['principle_id', 'type', 'status'], 'pex_principle_type_status_index');
            });
        }
    }

    /**
     * Sem drop de propósito: esta migration só acrescenta (ver DATABASE_SAFETY.md).
     */
    public function down(): void {}
};
