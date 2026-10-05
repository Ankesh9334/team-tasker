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
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'company_name')) {
                $table->string('company_name')->nullable()->after('company');
            }
            if (!Schema::hasColumn('clients', 'website')) {
                $table->string('website')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('clients', 'client_type')) {
                $table->string('client_type')->nullable()->after('website');
            }
            if (!Schema::hasColumn('clients', 'email_opt_out')) {
                $table->boolean('email_opt_out')->default(false)->after('status');
            }
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'website', 'client_type', 'email_opt_out']);
        });
    }
};
