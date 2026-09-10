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
        Schema::create('communication_providers', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('channel');
            $table->string('channel_name')->nullable();
            $table->unsignedInteger('channel_position')->default(0);
            $table->string('provider');
            $table->string('name')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('priority')->default(1);
            $table->text('credentials')->nullable();
            $table->json('fields')->nullable();
            $table->timestamps();
            $table->unique(['channel', 'provider']);
            $table->index(['channel', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_providers');
    }
};
