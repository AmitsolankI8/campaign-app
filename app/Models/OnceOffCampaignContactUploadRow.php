<?php

namespace App\Models;

use App\Enums\ContactUploadRowStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $contact_import_id
 * @property int $row_number
 * @property int $existing_count
 * @property string $first_name
 * @property string|null $last_name
 * @property string $number
 * @property string $normalized_number
 * @property string|null $email
 * @property ContactUploadRowStatus $status
 * @property string|null $error
 * @property int|null $contact_id
 * @property array<string, string|null>|null $before_values
 * @property Carbon|null $synced_at
 * @property-read OnceOffCampaignContactImport $contactImport
 */
class OnceOffCampaignContactUploadRow extends Model
{
    use HasPublicId;

    /** @var list<string> */
    protected $fillable = ['row_number', 'first_name', 'last_name', 'number', 'normalized_number', 'email', 'status', 'error', 'contact_id', 'before_values', 'synced_at'];

    /** @var list<string> */
    protected $hidden = ['id', 'contact_import_id', 'contact_id'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => ContactUploadRowStatus::DEFAULT];

    /** @var array<string, string> */
    protected $casts = ['status' => ContactUploadRowStatus::class, 'before_values' => 'array', 'synced_at' => 'datetime'];

    /** @return BelongsTo<OnceOffCampaignContactImport, $this> */
    public function contactImport(): BelongsTo
    {
        return $this->belongsTo(OnceOffCampaignContactImport::class);
    }

    /** @return array{first_name: string, last_name: string|null, number: string, email: string|null} */
    public function contactValues(): array
    {
        return ['first_name' => $this->first_name, 'last_name' => $this->last_name, 'number' => $this->number, 'email' => $this->email];
    }
}
