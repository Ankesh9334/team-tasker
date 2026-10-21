<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_requests', 'is_paid')) {
                // false = personal (unpaid) leave, salary katti hai
                // true  = company ki paid leave, salary nahi katti
                // Sirf type = 'Leave' ke liye matlab rakhta hai.
                $table->boolean('is_paid')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_requests', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
        });
    }
};
