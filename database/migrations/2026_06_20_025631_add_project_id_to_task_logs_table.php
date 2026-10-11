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
        Schema::table('task_logs', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->unsignedBigInteger('task_id')->nullable()->change();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_logs', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            // Revert task_id to not nullable
            $table->dropForeign(['task_id']);
            $table->unsignedBigInteger('task_id')->nullable(false)->change();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }
};
