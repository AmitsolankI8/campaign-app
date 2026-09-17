<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationWorkStatus: int
{
    use HasOptions;

    case Pending = 1;
    case Processing = 2;
    case Completed = 3;
    case Cancelled = 4;
    case Expired = 5;
    case Failed = 6;
}
