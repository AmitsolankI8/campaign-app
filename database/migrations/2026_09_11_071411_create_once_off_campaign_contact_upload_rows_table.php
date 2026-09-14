<?php

use App\Enums\ContactUploadRowStatus;
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
        Schema::create('once_off_campaign_contact_upload_rows', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('contact_import_id')->constrained('once_off_campaign_contact_imports', indexName: 'upload_rows_import_fk')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('number', 32);
            $table->string('normalized_number', 32);
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('status')->default(ContactUploadRowStatus::DEFAULT);
            $table->text('error')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('once_off_campaign_contacts', indexName: 'upload_rows_contact_fk')->nullOnDelete();
            $table->json('before_values')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['contact_import_id', 'row_number'], 'upload_rows_import_row_unique');
            $table->index(['contact_import_id', 'status'], 'upload_rows_import_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('once_off_campaign_contact_upload_rows');
    }
};
