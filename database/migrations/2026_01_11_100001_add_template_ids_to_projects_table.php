<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignUlid('prd_template_id')
                ->nullable()
                ->after('preferred_model')
                ->constrained('templates')
                ->nullOnDelete();
            $table->foreignUlid('tech_template_id')
                ->nullable()
                ->after('prd_template_id')
                ->constrained('templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prd_template_id');
            $table->dropConstrainedForeignId('tech_template_id');
        });
    }
};
