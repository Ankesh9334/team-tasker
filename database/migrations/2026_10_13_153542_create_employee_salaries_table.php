
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

            $table->decimal('monthly_salary', 12, 2);

            // Weekdays enabled as working days, e.g. [1,2,3,4,5,6].
            // ISO weekday numbers: Monday=1, Sunday=7.
            $table->json('working_days');

            $table->date('effective_from');
            $table->date('effective_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'effective_from']);
            $table->index(['is_active', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
