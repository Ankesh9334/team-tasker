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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // direct_call, group_call, project_meeting, task_meeting, scheduled_meeting
            $table->string('mode'); // audio, video
            $table->string('provider')->default('livekit');
            $table->string('room_name')->unique();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('duration')->nullable(); // in minutes
            $table->string('status')->default('scheduled'); // scheduled, ringing, active, completed, cancelled, rejected, missed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['task_id', 'status']);
            $table->index('created_by');
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('participant'); // host, participant
            $table->string('status')->default('invited'); // invited, ringing, accepted, rejected, joined, left
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
            $table->index(['meeting_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
    }
};
