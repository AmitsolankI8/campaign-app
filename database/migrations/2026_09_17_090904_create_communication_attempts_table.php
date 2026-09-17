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
        Schema::create('communication_attempts', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('communication_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_account_id')->constrained('communication_provider_accounts')->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->unsignedTinyInteger('status')->default(CommunicationStatus::Processing->value);
            $table->boolean('retryable')->default(false);
            $table->string('provider_message_id')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->json('request_metadata')->nullable();
            $table->json('response_metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('provider_event_at')->nullable();
            $table->unique(['communication_id', 'attempt_number'], 'communication_attempt_number_unique');
            $table->unique(['provider_account_id', 'provider_message_id'], 'communication_attempt_provider_message_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_attempts');
    }
};
