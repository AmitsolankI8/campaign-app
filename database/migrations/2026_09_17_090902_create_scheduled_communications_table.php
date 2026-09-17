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
        Schema::create('scheduled_communications', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->foreignId('campaign_schedule_id')->nullable()->constrained('once_off_campaign_schedules')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('once_off_campaign_contacts')->restrictOnDelete();
            $table->foreignId('channel_id')->constrained('communication_channels')->restrictOnDelete();
            $table->unsignedInteger('attempt_number')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('idempotency_key')->unique();
            $table->boolean('replaces_attempt')->default(false);
            $table->dateTime('scheduled_at');
            $table->string('timezone')->default('UTC');
            $table->unsignedTinyInteger('source');
            $table->unsignedTinyInteger('status')->default(CommunicationWorkStatus::Pending->value);
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('dispatch_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->index(['status', 'scheduled_at'], 'scheduled_communications_due_index');
            $table->index(['status', 'dispatch_expires_at'], 'scheduled_communications_recovery_index');
            $table->index(['campaign_schedule_id', 'contact_id', 'replaces_attempt'], 'scheduled_communications_override_index');
            $table->timestamp('completion_checked_at')->nullable();
            $table->index(['status', 'completion_checked_at'], 'callback_completion_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_communications');
    }
};
