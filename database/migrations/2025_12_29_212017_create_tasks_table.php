<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('epic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('story_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('plan_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('plan_run_step_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('acceptance_criteria')->nullable();
            $table->string('estimate')->nullable();
            $table->json('labels')->nullable();
            $table->json('depends_on')->nullable();
            $table->string('status')->default('todo');
            $table->decimal('board_order', 10, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status', 'board_order']);
            $table->index(['project_id', 'created_at']);
            $table->index('plan_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
