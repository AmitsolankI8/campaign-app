<?php

namespace App\Contracts\Communication;

use App\Services\Communication\Data\ProviderResponse;
use App\Services\Communication\Data\SmsMessage;

interface SmsProvider
{
    public function send(SmsMessage $message): ProviderResponse;
}
