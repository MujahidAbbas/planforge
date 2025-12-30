<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // prd, tech
            $table->ulid('current_version_id')->nullable(); // FK added after document_versions table
            $table->timestamps();

            $table->unique(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
