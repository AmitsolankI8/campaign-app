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
 * @property int $attempt_count
 * @property Carbon $scheduled_at
 * @property string $channel
 * @property-read Campaign $campaign
 */
class OnceOffCampaignSchedule extends Model
{
    use HasPublicId;

    /** @var list<string> */
    protected $fillable = ['attempt_count', 'scheduled_at', 'channel'];

    /** @var list<string> */
    protected $hidden = ['id', 'campaign_id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'scheduled_at' => 'datetime'];
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
