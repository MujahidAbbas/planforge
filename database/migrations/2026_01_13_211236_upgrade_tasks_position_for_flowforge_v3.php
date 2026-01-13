<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\Flowforge\Services\DecimalPosition;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new decimal column
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('position_new', 20, 10)->nullable()->after('position');
        });

        // Step 2: Convert existing positions maintaining order per status column
        $statuses = DB::table('tasks')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('status');

        foreach ($statuses as $status) {
            $tasks = DB::table('tasks')
                ->where('status', $status)
                ->whereNull('deleted_at')
                ->orderBy('position')
                ->get();

            $lastPosition = null;
            foreach ($tasks as $task) {
                $newPosition = $lastPosition === null
                    ? DecimalPosition::forEmptyColumn()
                    : DecimalPosition::after($lastPosition);

                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['position_new' => $newPosition]);

                $lastPosition = $newPosition;
            }
        }

        // Step 3: Drop old index
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_project_id_status_position_index');
        });

        // Step 4: Drop old column
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        // Step 5: Rename new column
        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('position_new', 'position');
        });

        // Step 6: Add new index
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['project_id', 'status', 'position'], 'tasks_project_id_status_position_index');
        });
    }

    public function down(): void
    {
        // Step 1: Drop new index
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_project_id_status_position_index');
        });

        // Step 2: Add temporary column for old format
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('position_old')->nullable()->after('position');
        });

        // Step 3: Convert back to string format (positions will need regeneration)
        DB::table('tasks')->update(['position_old' => DB::raw('CAST(position AS CHAR)')]);

        // Step 4: Drop decimal column
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        // Step 5: Rename back
        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('position_old', 'position');
        });

        // Step 6: Recreate index
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['project_id', 'status', 'position'], 'tasks_project_id_status_position_index');
        });
    }
};
