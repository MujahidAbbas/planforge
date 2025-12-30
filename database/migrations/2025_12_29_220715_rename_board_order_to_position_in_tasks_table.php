<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Drop old index that includes board_order
            $table->dropIndex(['project_id', 'status', 'board_order']);

            // Drop the old column
            $table->dropColumn('board_order');
        });

        Schema::table('tasks', function (Blueprint $table) {
            // Add Flowforge position column with proper collation
            $table->flowforgePositionColumn('position');

            // Recreate index with new column
            $table->index(['project_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status', 'position']);
            $table->dropColumn('position');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('board_order', 10, 4)->default(0);
            $table->index(['project_id', 'status', 'board_order']);
        });
    }
};
