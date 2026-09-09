<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $public_id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $full_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UserPreference|null $preferences
 */
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicId, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** @var list<string> */
    protected $appends = [
        'full_name',
    ];

    /** @var list<string> */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
    ];

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    public function getPasskeyDisplayName(): string
    {
        return $this->full_name;
    }

    /**
     * @return HasOne<UserPreference, $this>
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }
}
