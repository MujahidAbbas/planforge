<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_prd_template_id', 26)
                ->nullable()
                ->after('remember_token');
            $table->string('default_tech_template_id', 26)
                ->nullable()
                ->after('default_prd_template_id');

            $table->foreign('default_prd_template_id')
                ->references('id')
                ->on('templates')
                ->nullOnDelete();
            $table->foreign('default_tech_template_id')
                ->references('id')
                ->on('templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_prd_template_id']);
            $table->dropForeign(['default_tech_template_id']);
            $table->dropColumn(['default_prd_template_id', 'default_tech_template_id']);
        });
    }
};
