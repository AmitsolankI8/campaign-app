<?php

namespace App\Models;

use App\Enums\CommunicationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $communication_id
 * @property int $provider_account_id
 * @property int $attempt_number
 * @property CommunicationStatus $status
 * @property bool $retryable
 * @property string|null $provider_message_id
 * @property string|null $error_code
 * @property string|null $error_message
 * @property array<string, mixed>|null $request_metadata
 * @property array<string, mixed>|null $response_metadata
 * @property Carbon|null $provider_event_at
 * @property Carbon $started_at
 * @property Carbon|null $completed_at
 * @property-read Communication $communication
 * @property-read CommunicationProviderAccount $providerAccount
 */
class CommunicationAttempt extends Model
{
    use HasPublicId;

    protected $fillable = ['communication_id', 'provider_account_id', 'attempt_number', 'status', 'retryable', 'provider_message_id', 'error_code', 'error_message', 'request_metadata', 'response_metadata', 'provider_event_at', 'started_at', 'completed_at'];

    protected $hidden = ['id', 'communication_id', 'provider_account_id'];

    protected $attributes = ['status' => CommunicationStatus::Processing->value, 'retryable' => false];

    protected function casts(): array
    {
        return ['id' => 'integer', 'communication_id' => 'integer', 'provider_account_id' => 'integer', 'attempt_number' => 'integer', 'status' => CommunicationStatus::class, 'retryable' => 'boolean', 'request_metadata' => 'array', 'response_metadata' => 'array', 'provider_event_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<Communication, $this> */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(Communication::class, 'communication_id');
    }

    /** @return BelongsTo<CommunicationProviderAccount, $this> */
    public function providerAccount(): BelongsTo
    {
        return $this->belongsTo(CommunicationProviderAccount::class, 'provider_account_id');
    }
}
