<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferenceTimezone extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'identifier',
        'display_name',
        'short_code',
    ];
}
