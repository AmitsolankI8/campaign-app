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
        Schema::create('communication_provider_accounts', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('provider_id')->constrained('communication_providers')->restrictOnDelete();
            $table->string('key')->default('default');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_active')->default(false);
            $table->unique(['provider_id', 'key']);
            $table->index(['is_active', 'priority']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_provider_accounts');
    }
};
