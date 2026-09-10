<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'campaign_type' => CampaignType::OnceOff->value,
            'status' => CampaignStatus::DEFAULT,
            'short_note' => fake()->optional()->sentence(),
        ];
    }

    public function onceOff(): static
    {
        return $this->state(['campaign_type' => CampaignType::OnceOff->value]);
    }

    public function ongoing(): static
    {
        return $this->state(['campaign_type' => CampaignType::Ongoing->value]);
    }

    public function batchProcessing(): static
    {
        return $this->state(['campaign_type' => CampaignType::BatchProcessing->value]);
    }

    public function status(CampaignStatus $status): static
    {
        return $this->state(['status' => $status->value]);
    }
}
