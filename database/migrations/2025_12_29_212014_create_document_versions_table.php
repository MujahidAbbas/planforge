<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('plan_run_step_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('content_md');
            $table->json('content_json')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
            $table->index('plan_run_id');
        });

        // Add FK constraint for current_version_id on documents
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('document_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('document_versions');
    }
};
