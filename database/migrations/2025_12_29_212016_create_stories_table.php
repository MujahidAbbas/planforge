<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('epic_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('acceptance_criteria')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['epic_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
