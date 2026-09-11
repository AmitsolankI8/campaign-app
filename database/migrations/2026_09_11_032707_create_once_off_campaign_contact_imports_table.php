<?php

use App\Enums\ContactImportStatus;
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
        Schema::create('once_off_campaign_contact_imports', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('contact_count');
            $table->unsignedTinyInteger('status')->default(ContactImportStatus::DEFAULT);
            $table->json('rows')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['campaign_id', 'status', 'created_at'], 'contact_imports_campaign_status_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('once_off_campaign_contact_imports');
    }
};
