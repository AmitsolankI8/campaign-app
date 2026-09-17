<?php

namespace App\Contracts\Communication;

use App\Services\Communication\Data\EmailMessage;
use App\Services\Communication\Data\ProviderResponse;

interface EmailProvider
{
    public function send(EmailMessage $message): ProviderResponse;
}
