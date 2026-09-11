<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string $number
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Campaign $campaign
 */
class OnceOffCampaignContact extends Model
{
    use HasPublicId;

    /** @var list<string> */
    protected $fillable = ['first_name', 'last_name', 'number', 'email'];

    /** @var list<string> */
    protected $hidden = ['id', 'campaign_id'];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
