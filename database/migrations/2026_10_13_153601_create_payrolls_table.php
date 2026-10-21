<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

            $table->foreignId('employee_salary_id')
            ->constrained('employee_salaries')
            ->restrictOnDelete();

            // Payroll month is stored as the first day of that month.
            $table->date('payroll_month');

            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->unsignedSmallInteger('scheduled_working_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('paid_leave_days')->default(0);
            $table->unsignedSmallInteger('unpaid_leave_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->decimal('half_days', 6, 2)->default(0);

            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('deduction_amount', 12, 2)->default(0);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('final_salary', 12, 2)->default(0);

            // Overtime is tracked but is not paid automatically.
            $table->decimal('overtime_hours', 8, 2)->default(0);

            // draft -> pending -> approved / rejected -> paid
            $table->string('status', 30)->default('draft');

            $table->foreignId('generated_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $table->foreignId('approved_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $table->timestamp('generated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Payment details (mark-as-paid par save hote hain).
            $table->string('payment_method', 30)->nullable();   // bank_transfer, upi, cheque, cash
            $table->string('payment_reference', 100)->nullable(); // UTR / cheque no.

            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'payroll_month'],
                'payroll_user_month_unique'
            );

            $table->index(['payroll_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};