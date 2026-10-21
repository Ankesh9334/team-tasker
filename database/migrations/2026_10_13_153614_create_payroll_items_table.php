
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_id')
            ->constrained('payrolls')
            ->cascadeOnDelete();

            $table->date('work_date');

            // present, half_day, absent, paid_leave, unpaid_leave, weekly_off
            $table->string('day_type', 30);

            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('deduction_amount', 12, 2)->default(0);

            $table->unsignedSmallInteger('working_minutes')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);

            // Optional reference to the existing attendance record.
            $table->foreignId('attendance_id')
            ->nullable()
            ->constrained('attendances')
            ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(
                ['payroll_id', 'work_date'],
                'payroll_item_date_unique'
            );

            $table->index(['work_date', 'day_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
