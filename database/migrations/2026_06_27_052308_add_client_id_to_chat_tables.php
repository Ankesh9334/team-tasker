<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('user_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            // user_id becomes nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('user_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            // user_id becomes nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
