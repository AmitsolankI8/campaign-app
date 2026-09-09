<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PreferenceFormat extends Model
{
    public const TYPE_NUMBER = 'number';

    public const TYPE_DATE = 'date';

    public const TYPE_TIME = 'time';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_NUMBER,
        self::TYPE_DATE,
        self::TYPE_TIME,
    ];

    /** @var list<string> */
    protected $fillable = [
        'type',
        'name',
        'display_name',
        'format',
        'example',
    ];

    /**
     * @param  Builder<PreferenceFormat>  $query
     * @return Builder<PreferenceFormat>
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
