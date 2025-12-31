<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUlid('task_set_id')->nullable()->after('plan_run_step_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable()->after('status');
            $table->string('priority')->default('med')->after('category');
            $table->json('source_refs')->nullable()->after('depends_on');

            $table->index('task_set_id');
            $table->index('category');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_set_id']);
            $table->dropIndex(['task_set_id']);
            $table->dropIndex(['category']);
            $table->dropIndex(['priority']);
            $table->dropColumn(['task_set_id', 'category', 'priority', 'source_refs']);
        });
    }
};
