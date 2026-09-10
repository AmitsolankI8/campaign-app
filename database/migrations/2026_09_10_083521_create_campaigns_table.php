<?php

use App\Enums\CampaignStatus;
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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('campaign_type')->comment('1 - once_off, 2 - ongoing, 3 - batch_processing')->index();
            $table->unsignedTinyInteger('status')->default(CampaignStatus::DEFAULT)->comment('1 - draft, 2 - launched, 3 - running, 4 - paused, 5 - cancelled')->index();
            $table->string('short_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
