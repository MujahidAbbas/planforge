<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('document_type'); // 'prd' | 'tech'
            $table->string('category')->nullable(); // For grouping in UI
            $table->json('sections');
            $table->text('ai_instructions')->nullable();
            $table->boolean('is_built_in')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_community')->default(false);
            $table->string('author')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['document_type', 'is_built_in']);
            $table->index(['user_id', 'document_type']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
