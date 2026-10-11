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
        // Support multi-guard authentication (both User and Admin models) by dropping the strict foreign key
        // constraint pointing solely to users(id), while retaining the performance indexes.
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            if (! Schema::hasColumn('attendances', 'approved_by_type')) {
                $table->string('approved_by_type')->nullable()->default('user')->after('approved_by');
            }
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            if (! Schema::hasColumn('attendance_logs', 'user_type')) {
                $table->string('user_type')->nullable()->default('user')->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'approved_by_type')) {
                $table->dropColumn('approved_by_type');
            }
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'user_type')) {
                $table->dropColumn('user_type');
            }
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
