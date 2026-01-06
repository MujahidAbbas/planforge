<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('integration_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('task_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 50); // Denormalized for faster queries
            $table->string('external_id', 255); // GitHub node_id
            $table->unsignedInteger('external_number')->nullable(); // Issue #123
            $table->string('external_url', 500)->nullable();
            $table->string('external_state', 50)->nullable(); // open, closed

            $table->string('sync_status', 20)->default('pending'); // pending, synced, failed, orphaned, conflict
            $table->text('sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            // Track what was last synced to detect changes
            $table->string('last_synced_hash', 64)->nullable(); // SHA256 of synced content

            $table->timestamps();

            $table->unique(['integration_id', 'task_id']);
            $table->unique(['provider', 'external_id']);
            $table->index(['task_id', 'provider']);
            $table->index(['sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_links');
    }
};
