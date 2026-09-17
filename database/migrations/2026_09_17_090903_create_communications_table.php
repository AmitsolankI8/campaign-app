<?php

use App\Enums\CommunicationStatus;
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
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->foreignId('campaign_schedule_id')->nullable()->constrained('once_off_campaign_schedules')->nullOnDelete();
            $table->foreignId('scheduled_communication_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->constrained('once_off_campaign_contacts')->restrictOnDelete();
            $table->foreignId('channel_id')->constrained('communication_channels')->restrictOnDelete();
            $table->unsignedTinyInteger('status')->default(CommunicationStatus::Pending->value);
            $table->string('idempotency_key')->unique();
            $table->ulid('correlation_id')->index();
            $table->dateTime('scheduled_at');
            $table->dateTime('next_attempt_at');
            $table->unsignedInteger('retry_count')->default(0);
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claim_expires_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('dispatch_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->index(['status', 'next_attempt_at'], 'communications_due_index');
            $table->index(['status', 'claim_expires_at'], 'communications_claim_index');
            $table->index(['campaign_id', 'status']);
            $table->timestamp('completion_checked_at')->nullable();
            $table->index(['status', 'completion_checked_at'], 'communication_completion_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
