<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_sets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('source_tech_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->foreignUlid('source_prd_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->foreignUlid('plan_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('plan_run_step_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_sets');
    }
};
