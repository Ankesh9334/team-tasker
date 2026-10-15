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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->index()->after('email_verified_at');
            });
        }

        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'last_seen_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->index()->after('email_verified_at');
            });
        }

        if (Schema::hasTable('admins') && !Schema::hasColumn('admins', 'last_seen_at')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->index()->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'last_seen_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }

        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'last_seen_at')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }
    }
};
