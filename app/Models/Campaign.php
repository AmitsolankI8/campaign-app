<?php

namespace App\Models;

use App\Enums\CampaignType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $short_note
 * @property CampaignType $campaign_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Campaign extends Model
{
    use HasPublicId;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'campaign_type',
        'short_note',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'campaign_type' => CampaignType::class,
    ];
}
