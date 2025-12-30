<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('queued');
            $table->string('provider');
            $table->string('model');
            $table->json('input_snapshot');
            $table->json('metrics')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_runs');
    }
};
