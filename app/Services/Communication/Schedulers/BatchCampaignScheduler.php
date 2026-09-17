<?php

namespace App\Services\Communication\Schedulers;

use App\Models\Campaign;
use LogicException;

class BatchCampaignScheduler
{
    public function schedule(Campaign $campaign): void
    {
        throw new LogicException('Scheduling rules for this campaign type have not been implemented.');
    }
}
