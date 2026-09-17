<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ScheduledCommunicationSource: int
{
    use HasOptions;

    case CampaignOverride = 1;
    case CustomerCallback = 2;
    case Manual = 3;
    case Retry = 4;
}
