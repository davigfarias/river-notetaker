<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->string('format');
            $table->string('scope');
            $table->foreignId('reference_material_id')->nullable()->constrained('reference_materials')->nullOnDelete();
            $table->string('search_query')->nullable();
            $table->string('status')->default('pending');
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['access_token_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
