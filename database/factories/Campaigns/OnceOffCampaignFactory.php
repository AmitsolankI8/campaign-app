<?php

namespace Database\Factories\Campaigns;

use App\Models\Campaigns\OnceOffCampaign;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OnceOffCampaign> */
class OnceOffCampaignFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return CampaignFactory::new()->definition();
    }
}
