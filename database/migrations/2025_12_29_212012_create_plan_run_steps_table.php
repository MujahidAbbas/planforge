<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_run_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_run_id')->constrained()->cascadeOnDelete();
            $table->string('step'); // prd, tech, breakdown, tasks
            $table->string('status')->default('queued');
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('provider');
            $table->string('model');
            $table->string('prompt_hash')->nullable();
            $table->json('request_meta')->nullable();
            $table->json('rate_limits')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['plan_run_id', 'step']);
            $table->index(['step', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_run_steps');
    }
};
