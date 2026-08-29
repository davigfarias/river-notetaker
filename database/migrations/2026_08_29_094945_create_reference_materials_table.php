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
        Schema::create('reference_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_token_id')->nullable()->constrained('access_tokens')->nullOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('type');
            $table->string('publisher')->nullable();
            $table->string('url')->nullable();
            $table->text('abnt_reference')->nullable();
            $table->timestamps();

            $table->index('access_token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_materials');
    }
};
