<?php

namespace App\Models;

use App\Support\UserPreferences;
use Database\Factories\UserPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /** @use HasFactory<UserPreferenceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'country_preference_id',
        'timezone_preference_id',
        'language_preference_id',
        'number_format_preference_id',
        'date_format_preference_id',
        'time_format_preference_id',
    ];

    protected static function booted(): void
    {
        static::saved(function (UserPreference $preferences): void {
            UserPreferences::forgetForUserId($preferences->user_id);
        });
        static::deleted(function (UserPreference $preferences): void {
            UserPreferences::forgetForUserId($preferences->user_id);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PreferenceCountry, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(PreferenceCountry::class, 'country_preference_id');
    }

    /**
     * @return BelongsTo<PreferenceTimezone, $this>
     */
    public function timezone(): BelongsTo
    {
        return $this->belongsTo(PreferenceTimezone::class, 'timezone_preference_id');
    }

    /**
     * @return BelongsTo<PreferenceLanguage, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(PreferenceLanguage::class, 'language_preference_id');
    }

    /**
     * @return BelongsTo<PreferenceFormat, $this>
     */
    public function numberFormat(): BelongsTo
    {
        return $this->belongsTo(PreferenceFormat::class, 'number_format_preference_id');
    }

    /**
     * @return BelongsTo<PreferenceFormat, $this>
     */
    public function dateFormat(): BelongsTo
    {
        return $this->belongsTo(PreferenceFormat::class, 'date_format_preference_id');
    }

    /**
     * @return BelongsTo<PreferenceFormat, $this>
     */
    public function timeFormat(): BelongsTo
    {
        return $this->belongsTo(PreferenceFormat::class, 'time_format_preference_id');
    }
}
