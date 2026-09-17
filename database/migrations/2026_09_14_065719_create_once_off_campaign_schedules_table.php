<?php

use App\Enums\CommunicationWorkStatus;
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
        Schema::create('once_off_campaign_schedules', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->dateTime('scheduled_at');
            $table->foreignId('channel_id')->constrained('communication_channels')->restrictOnDelete();
            $table->string('timezone')->default('UTC');
            $table->unsignedTinyInteger('status')->default(CommunicationWorkStatus::Pending->value);
            $table->unsignedBigInteger('last_contact_id')->default(0);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('dispatch_expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('completion_checked_at')->nullable();
            $table->index(['status', 'completion_checked_at'], 'schedule_completion_index');
            $table->timestamps();
            $table->unique(['campaign_id', 'attempt_number'], 'campaign_schedule_attempt_unique');
            $table->index(['status', 'scheduled_at'], 'once_off_schedules_due_index');
            $table->index(['status', 'dispatch_expires_at'], 'once_off_schedules_recovery_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('once_off_campaign_schedules');
    }
};
