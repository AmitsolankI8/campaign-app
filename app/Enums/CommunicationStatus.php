<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationStatus: int
{
    use HasOptions;

    case Pending = 1;
    case Processing = 2;
    case Accepted = 3;
    case Sent = 4;
    case Delivered = 5;
    case Failed = 6;
    case Cancelled = 7;
    case Unknown = 8;

    public function successful(): bool
    {
        return in_array($this, [self::Accepted, self::Sent, self::Delivered], true);
    }

    public function finished(): bool
    {
        return ! in_array($this, [self::Pending, self::Processing, self::Unknown], true);
    }
}
