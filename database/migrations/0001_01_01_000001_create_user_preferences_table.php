<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('country_preference_id')->constrained('preference_countries')->restrictOnDelete();
            $table->foreignId('timezone_preference_id')->constrained('preference_timezones')->restrictOnDelete();
            $table->foreignId('language_preference_id')->constrained('preference_languages')->restrictOnDelete();
            $table->foreignId('number_format_preference_id')->constrained('preference_formats')->restrictOnDelete();
            $table->foreignId('date_format_preference_id')->constrained('preference_formats')->restrictOnDelete();
            $table->foreignId('time_format_preference_id')->constrained('preference_formats')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
