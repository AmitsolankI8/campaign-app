<?php

namespace App\Models;

use App\Enums\ContactImportStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property string $file_name
 * @property int $contact_count
 * @property ContactImportStatus $status
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
    protected $fillable = ['file_name', 'contact_count', 'rows', 'status', 'synced_at'];

    /** @var list<string> */
    protected $hidden = ['id', 'campaign_id', 'rows'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => ContactImportStatus::DEFAULT];

    /** @var array<string, string> */
    protected $casts = ['status' => ContactImportStatus::class, 'rows' => 'array', 'synced_at' => 'datetime'];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
