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
        Schema::create('principle_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principle_topic_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('principles', function (Blueprint $table) {
            $table->foreignId('principle_category_id')->nullable()->after('principle_topic_id')
                ->constrained('principle_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('principles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('principle_category_id');
        });

        Schema::dropIfExists('principle_categories');
    }
};
