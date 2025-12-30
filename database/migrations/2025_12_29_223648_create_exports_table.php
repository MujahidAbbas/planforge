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
        Schema::create('exports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // ExportType enum
            $table->string('status')->default('building'); // ExportStatus enum
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
