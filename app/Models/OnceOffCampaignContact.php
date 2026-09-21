<?php

namespace App\Models;

use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string $number
 * @property string|null $normalized_number
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OnceOffCampaign $campaign
 * @property-read Collection<int, Communication> $communications
 */
class OnceOffCampaignContact extends Model
{
    use HasPublicId, SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['first_name', 'last_name', 'number', 'email'];

    protected static function booted(): void
    {
        static::saving(function (self $contact): void {
            $contact->normalized_number = preg_replace('/\D/', '', $contact->number);
        });
    }

    /** @var list<string> */
    protected $hidden = ['id', 'campaign_id'];

    /** @return BelongsTo<OnceOffCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OnceOffCampaign::class, 'campaign_id');
    }

    /** @return HasMany<Communication, $this> */
    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class, 'contact_id');
    }
}
