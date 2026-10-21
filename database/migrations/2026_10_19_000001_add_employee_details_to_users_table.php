<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'designation')) {
                $table->string('designation', 100)->nullable();   // job title, jaise "QA Engineer"
            }
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable();
            }
            if (! Schema::hasColumn('users', 'joining_date')) {
                $table->date('joining_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['designation', 'department', 'joining_date'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
