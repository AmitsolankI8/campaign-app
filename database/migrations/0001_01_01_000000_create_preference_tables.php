<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preference_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('identifier')->unique();
            $table->string('display_name');
            $table->string('short_code', 16)->unique();
            $table->timestamps();
        });

        Schema::create('preference_timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('identifier')->unique();
            $table->string('display_name');
            $table->string('short_code', 16)->unique();
            $table->timestamps();
        });

        Schema::create('preference_languages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('identifier')->unique();
            $table->string('display_name');
            $table->string('short_code', 16)->unique();
            $table->timestamps();
        });

        Schema::create('preference_formats', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('name');
            $table->string('display_name');
            $table->string('format');
            $table->string('example');
            $table->timestamps();

            $table->unique(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preference_formats');
        Schema::dropIfExists('preference_languages');
        Schema::dropIfExists('preference_timezones');
        Schema::dropIfExists('preference_countries');
    }
};
