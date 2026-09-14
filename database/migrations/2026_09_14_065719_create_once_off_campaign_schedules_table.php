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
        Schema::create('once_off_campaign_schedules', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_count');
            $table->dateTime('scheduled_at');
            $table->string('channel');
            $table->timestamps();
            $table->unique(['campaign_id', 'attempt_count'], 'campaign_schedule_attempt_unique');
            $table->index('scheduled_at');
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
