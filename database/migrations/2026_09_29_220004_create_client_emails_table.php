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
        Schema::create('client_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('client_email_template_id')->nullable()->constrained('client_email_templates')->nullOnDelete();
            $table->string('campaign_id')->nullable()->index();
            $table->string('campaign_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->json('attachments')->nullable();
            $table->string('status')->default('queued'); // draft, queued, sending, sent, failed, cancelled
            $table->string('message_id')->nullable()->index();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->text('failure_reason')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_emails');
    }
};
