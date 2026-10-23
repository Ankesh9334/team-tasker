<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Har column (naam => definition). Jo column pehle se hai use skip karega,
     * isliye ye migration dobara chalane par bhi error nahi dega.
     */
    private function columns(): array
    {
        return [
            // Personal
            'phone'                   => fn (Blueprint $t) => $t->string('phone', 20)->nullable(),
            'date_of_birth'           => fn (Blueprint $t) => $t->date('date_of_birth')->nullable(),
            'gender'                  => fn (Blueprint $t) => $t->string('gender', 10)->nullable(),
            'address'                 => fn (Blueprint $t) => $t->text('address')->nullable(),
            'emergency_contact_name'  => fn (Blueprint $t) => $t->string('emergency_contact_name')->nullable(),
            'emergency_contact_phone' => fn (Blueprint $t) => $t->string('emergency_contact_phone', 20)->nullable(),

            // Job
            'employee_code'           => fn (Blueprint $t) => $t->string('employee_code', 50)->nullable()->unique(),
            'designation'             => fn (Blueprint $t) => $t->string('designation', 100)->nullable(),
            'department'              => fn (Blueprint $t) => $t->string('department', 100)->nullable(),
            'joining_date'            => fn (Blueprint $t) => $t->date('joining_date')->nullable(),

            // Documents (encrypted store hoga, isliye text)
            'pan_number'              => fn (Blueprint $t) => $t->string('pan_number', 20)->nullable(),
            'aadhaar_number'          => fn (Blueprint $t) => $t->text('aadhaar_number')->nullable(),

            // Bank
            'bank_name'               => fn (Blueprint $t) => $t->string('bank_name')->nullable(),
            'bank_account_number'     => fn (Blueprint $t) => $t->text('bank_account_number')->nullable(),
            'bank_ifsc'               => fn (Blueprint $t) => $t->string('bank_ifsc', 20)->nullable(),
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $name => $define) {
            if (! Schema::hasColumn('users', $name)) {
                Schema::table('users', function (Blueprint $table) use ($define) {
                    $define($table);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->columns()) as $name) {
            if (Schema::hasColumn('users', $name)) {
                Schema::table('users', function (Blueprint $table) use ($name) {
                    if ($name === 'employee_code') {
                        $table->dropUnique(['employee_code']);
                    }
                    $table->dropColumn($name);
                });
            }
        }
    }
};