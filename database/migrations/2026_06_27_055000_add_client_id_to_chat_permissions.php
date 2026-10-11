<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_user_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('user_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->unsignedBigInteger('allowed_client_id')->nullable()->after('allowed_user_id');
            $table->foreign('allowed_client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('allowed_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chat_user_permissions', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
            $table->dropForeign(['allowed_client_id']);
            $table->dropColumn('allowed_client_id');
        });
    }
};
