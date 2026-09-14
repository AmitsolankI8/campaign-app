<?php

namespace App\Models;

use App\Enums\ContactImportStatus;
use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadSource;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property string $file_name
 * @property int $contact_count
 * @property ContactImportStatus $status
 * @property ContactUploadMode $mode
 * @property ContactUploadSource $source
 * @property string|null $file_path
 * @property int|null $uploaded_by
 * @property int $removed_count
 * @property int $processed_count
 * @property int $uploaded_rows_count
 * @property-read User|null $uploader
 * @property list<array{first_name: string, last_name: string|null, number: string, email: string|null}>|null $rows
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Campaign $campaign
 */
class OnceOffCampaignContactImport extends Model
{
    use HasPublicId;

    /** @var list<string> */
    protected $fillable = ['file_name', 'contact_count', 'rows', 'status', 'synced_at', 'mode', 'source', 'file_path', 'uploaded_by', 'removed_count'];

    /** @var list<string> */
    protected $hidden = ['id', 'campaign_id', 'rows', 'file_path', 'uploaded_by'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => ContactImportStatus::DEFAULT, 'source' => ContactUploadSource::DEFAULT, 'mode' => ContactUploadMode::DEFAULT, 'removed_count' => 0];

    /** @var array<string, string> */
    protected $casts = ['status' => ContactImportStatus::class, 'source' => ContactUploadSource::class, 'mode' => ContactUploadMode::class, 'rows' => 'array', 'synced_at' => 'datetime'];

    /** @return HasMany<OnceOffCampaignContactUploadRow, $this> */
    public function uploadedRows(): HasMany
    {
        return $this->hasMany(OnceOffCampaignContactUploadRow::class, 'contact_import_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
