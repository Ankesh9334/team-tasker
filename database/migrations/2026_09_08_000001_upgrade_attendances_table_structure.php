<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'attendance_date')) {
                $table->date('attendance_date')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('attendances', 'check_in')) {
                $table->time('check_in')->nullable()->after('attendance_date');
            }
            if (! Schema::hasColumn('attendances', 'check_out')) {
                $table->time('check_out')->nullable()->after('check_in');
            }
            if (! Schema::hasColumn('attendances', 'working_minutes')) {
                $table->unsignedInteger('working_minutes')->default(0)->after('check_out');
            }
            if (! Schema::hasColumn('attendances', 'attendance_status')) {
                $table->enum('attendance_status', ['present', 'absent', 'half_day'])->default('present')->after('working_minutes');
            }
            if (! Schema::hasColumn('attendances', 'approval_status')) {
                $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])->default('draft')->after('attendance_status');
            }
            if (! Schema::hasColumn('attendances', 'remarks')) {
                $table->text('remarks')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('attendances', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('attendances', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('attendances', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('attendances', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_by');
            }
        });

        // Safe backfill of historical data if columns exist
        if (Schema::hasColumn('attendances', 'date')) {
            DB::statement('UPDATE attendances SET attendance_date = `date` WHERE attendance_date IS NULL');
        }
        if (Schema::hasColumn('attendances', 'clock_in')) {
            DB::statement('UPDATE attendances SET check_in = clock_in WHERE check_in IS NULL');
        }
        if (Schema::hasColumn('attendances', 'clock_out')) {
            DB::statement('UPDATE attendances SET check_out = clock_out WHERE check_out IS NULL');
        }
        if (Schema::hasColumn('attendances', 'notes')) {
            DB::statement('UPDATE attendances SET remarks = notes WHERE remarks IS NULL');
        }
        if (Schema::hasColumn('attendances', 'work_hours')) {
            DB::statement('UPDATE attendances SET working_minutes = ROUND(COALESCE(work_hours, 0) * 60) WHERE working_minutes = 0');
        }
        if (Schema::hasColumn('attendances', 'status')) {
            DB::statement("UPDATE attendances SET 
                attendance_status = CASE 
                    WHEN status = 'Absent' THEN 'absent'
                    WHEN status = 'Half-Day' THEN 'half_day'
                    ELSE 'present'
                END,
                approval_status = 'approved',
                approved_at = COALESCE(updated_at, created_at)
                WHERE approval_status = 'draft' AND (clock_in IS NOT NULL OR status = 'Absent')");
        }

        // Add indexes
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['attendance_date', 'approval_status'], 'idx_att_date_approval');
            $table->index(['attendance_date', 'attendance_status'], 'idx_att_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_date_approval');
            $table->dropIndex('idx_att_date_status');
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'attendance_date',
                'check_in',
                'check_out',
                'working_minutes',
                'attendance_status',
                'approval_status',
                'remarks',
                'submitted_at',
                'approved_at',
                'approved_by',
                'rejection_reason',
            ]);
        });
    }
};
