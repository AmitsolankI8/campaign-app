<?php

namespace App\Http\Requests\Campaign;

use App\Models\Campaigns\OnceOffCampaign;
use App\Models\CommunicationChannel;
use App\Models\OnceOffCampaignSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOnceOffCampaignScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true
            && $this->user()->can('campaigns.edit');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var OnceOffCampaign $campaign */
        $campaign = $this->route('campaign');

        return [
            'schedules' => ['required', 'array', 'list', 'min:1'],
            'schedules.*' => ['required', 'array:id,scheduled_at,channel'],
            'schedules.*.id' => ['nullable', 'ulid', 'distinct', Rule::exists(OnceOffCampaignSchedule::class, 'public_id')->where('campaign_id', $campaign->id)],
            'schedules.*.scheduled_at' => ['required', 'date_format:Y-m-d\TH:i:s.v\Z'],
            'schedules.*.channel' => ['required', 'string', Rule::exists(CommunicationChannel::class, 'code')->where('is_active', true)],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var list<array{scheduled_at: string}> $attempts */
            $attempts = $this->input('schedules');
            foreach ($attempts as $index => $attempt) {
                if ($index > 0 && Carbon::parse($attempt['scheduled_at'])->lessThanOrEqualTo(
                    Carbon::parse($attempts[$index - 1]['scheduled_at']),
                )) {
                    $validator->errors()->add(
                        "schedules.{$index}.scheduled_at",
                        __('Schedule this follow-up later than the previous attempt.'),
                    );
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'schedules.required' => __('Add at least one schedule attempt.'),
            'schedules.min' => __('Keep at least one schedule attempt.'),
            'schedules.*.id.exists' => __('This attempt is no longer available. Reload the schedule.'),
            'schedules.*.scheduled_at.required' => __('Select a scheduled date and time.'),
            'schedules.*.scheduled_at.date_format' => __('Select a valid scheduled date and time.'),
            'schedules.*.channel.required' => __('Select a channel.'),
            'schedules.*.channel.exists' => __('Select an available communication channel.'),
        ];
    }
}
