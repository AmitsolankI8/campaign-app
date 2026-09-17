<?php

namespace App\Contracts\Communication;

use App\Services\Communication\Data\ProviderResponse;
use App\Services\Communication\Data\VoiceMessage;

interface VoiceProvider
{
    public function call(VoiceMessage $message): ProviderResponse;
}
