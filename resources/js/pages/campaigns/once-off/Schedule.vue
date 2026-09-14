<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { index } from '@/actions/App/Http/Controllers/CampaignController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import {
    show as showSchedule,
    update,
} from '@/actions/App/Http/Controllers/OnceOffCampaignScheduleController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useDateTimeFormat } from '@/composables/useDateTimeFormat';
import { usePermissions } from '@/composables/usePermissions';
import CampaignReadOnlyNotice from '../components/CampaignReadOnlyNotice.vue';
import { CAMPAIGN_STATUS_KEY } from '../types';
import type {
    Campaign,
    CampaignScheduleChannel,
    OnceOffCampaignSchedule,
} from '../types';

const props = defineProps<{
    campaign: Campaign;
    schedules: OnceOffCampaignSchedule[];
    channels: CampaignScheduleChannel[];
}>();

defineOptions({
    layout: ({ campaign }: { campaign: Campaign }) => ({
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: campaign.name, href: show(campaign.id) },
            { title: 'Schedule', href: showSchedule(campaign.id) },
        ],
    }),
});

const { hasPermissions } = usePermissions();
const canEdit = computed(
    () =>
        hasPermissions(['campaigns.view', 'campaigns.edit'], true) &&
        props.campaign.status.key === CAMPAIGN_STATUS_KEY.draft,
);
const { userTimezone, formatDateTime, formatDateTimeInput, toUtcIso } =
    useDateTimeFormat();
let nextKey = 0;
const makeAttempt = (schedule?: OnceOffCampaignSchedule) => ({
    key: ++nextKey,
    id: schedule?.id ?? null,
    scheduled_at: formatDateTimeInput(schedule?.scheduled_at) ?? '',
    channel: schedule?.channel ?? '',
});
const initialAttempts = () =>
    props.schedules.length ? props.schedules.map(makeAttempt) : [makeAttempt()];
const form = useForm({ schedules: initialAttempts() });
const errors = computed(
    () => form.errors as Record<string, string | undefined>,
);
const attemptLabel = (position: number) =>
    position === 0 ? 'First attempt' : `Follow-up attempt ${position}`;
const channelLabel = (channel: string) =>
    props.channels.find((option) => option.value === channel)?.label ?? channel;

function addAttempt() {
    if (!canEdit.value || form.processing) {
        return;
    }

    const previous = form.schedules[form.schedules.length - 1];
    const previousUtc = scheduledAtUtc(previous.scheduled_at);
    const followUp = makeAttempt();
    followUp.channel = previous.channel;
    followUp.scheduled_at = previousUtc
        ? (formatDateTimeInput(
              new Date(Date.parse(previousUtc) + 24 * 60 * 60 * 1000),
          ) ?? '')
        : '';
    form.schedules.push(followUp);
    form.clearErrors();
}

function removeAttempt(position: number) {
    if (!canEdit.value || form.processing || position === 0) {
        return;
    }

    form.schedules.splice(position, 1);
    form.clearErrors();
}

function scheduledAtUtc(value: string) {
    return toUtcIso(
        value,
        value.length === 16 ? 'YYYY-MM-DDTHH:mm' : 'YYYY-MM-DDTHH:mm:ss',
    );
}

function submit() {
    if (!canEdit.value || form.processing) {
        return;
    }

    form.clearErrors();
    let hasInvalidOrder = false;

    for (let position = 1; position < form.schedules.length; position++) {
        const previous = scheduledAtUtc(
            form.schedules[position - 1].scheduled_at,
        );
        const current = scheduledAtUtc(form.schedules[position].scheduled_at);

        if (
            previous &&
            current &&
            Date.parse(current) <= Date.parse(previous)
        ) {
            form.setError(
                `schedules.${position}.scheduled_at`,
                'Schedule this follow-up later than the previous attempt.',
            );
            hasInvalidOrder = true;
        }
    }

    if (hasInvalidOrder) {
        return;
    }

    form.transform((data) => ({
        schedules: data.schedules.map((attempt) => ({
            id: attempt.id,
            scheduled_at: scheduledAtUtc(attempt.scheduled_at),
            channel: attempt.channel,
        })),
    })).put(update.url(props.campaign.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults({ schedules: initialAttempts() });
            form.reset();
            form.clearErrors();
        },
    });
}
</script>

<template>
    <Head :title="`Schedule: ${campaign.name}`" />

    <CampaignReadOnlyNotice :status="campaign.status">
        You can view the schedule, but changes are allowed only while the
        campaign is draft.
    </CampaignReadOnlyNotice>

    <Card>
        <CardHeader>
            <CardTitle>Schedule</CardTitle>
            <CardDescription>
                Plan the first attempt and any follow-ups. All times are in
                {{ userTimezone }}.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form
                v-if="canEdit"
                class="space-y-6"
                novalidate
                @submit.prevent="submit"
            >
                <InputError :message="form.errors.schedules" />
                <p
                    v-if="!channels.length"
                    class="text-sm text-muted-foreground"
                    role="status"
                >
                    No communication channels are available. Set up
                    communication channels before saving a schedule.
                </p>

                <div class="space-y-4">
                    <fieldset
                        v-for="(attempt, position) in form.schedules"
                        :key="attempt.key"
                        :disabled="form.processing"
                        class="min-w-0 rounded-lg border p-4"
                    >
                        <legend class="px-2 text-sm font-semibold">
                            {{ attemptLabel(position) }}
                        </legend>
                        <div class="space-y-4">
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <p class="text-sm text-muted-foreground">
                                    Attempt {{ position + 1 }} of
                                    {{ form.schedules.length }}
                                </p>
                                <Button
                                    v-if="position > 0"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    :disabled="form.processing"
                                    :aria-label="`Remove follow-up attempt ${position}`"
                                    title="Remove follow-up"
                                    @click="removeAttempt(position)"
                                    ><Trash2 class="size-4"
                                /></Button>
                            </div>
                            <InputError
                                :message="errors[`schedules.${position}.id`]"
                            />
                            <InputError
                                :message="errors[`schedules.${position}`]"
                            />
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label
                                        :for="`scheduled-at-${attempt.key}`"
                                        required
                                        >Scheduled at</Label
                                    >
                                    <Input
                                        :id="`scheduled-at-${attempt.key}`"
                                        v-model="attempt.scheduled_at"
                                        type="datetime-local"
                                        step="1"
                                        aria-required="true"
                                        :aria-invalid="
                                            Boolean(
                                                errors[
                                                    `schedules.${position}.scheduled_at`
                                                ],
                                            )
                                        "
                                        :aria-describedby="`scheduled-at-error-${attempt.key}`"
                                    />
                                    <InputError
                                        :id="`scheduled-at-error-${attempt.key}`"
                                        :message="
                                            errors[
                                                `schedules.${position}.scheduled_at`
                                            ]
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label
                                        :for="`channel-${attempt.key}`"
                                        required
                                        >Channel</Label
                                    >
                                    <Select
                                        v-model="attempt.channel"
                                        :disabled="
                                            form.processing || !channels.length
                                        "
                                    >
                                        <SelectTrigger
                                            :id="`channel-${attempt.key}`"
                                            class="w-full"
                                            aria-required="true"
                                            :aria-invalid="
                                                Boolean(
                                                    errors[
                                                        `schedules.${position}.channel`
                                                    ],
                                                )
                                            "
                                            :aria-describedby="`channel-error-${attempt.key}`"
                                        >
                                            <SelectValue
                                                placeholder="Select channel"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="channel in channels"
                                                :key="channel.value"
                                                :value="channel.value"
                                            >
                                                {{ channel.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :id="`channel-error-${attempt.key}`"
                                        :message="
                                            errors[
                                                `schedules.${position}.channel`
                                            ]
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="addAttempt"
                    >
                        <Plus class="size-4" />
                        Add follow-up attempt
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !channels.length"
                    >
                        <Spinner v-if="form.processing" />
                        Save schedule
                    </Button>
                </div>
                <p class="text-sm text-muted-foreground">
                    At least one attempt is required. Each follow-up must be
                    later than the previous attempt. New follow-ups copy the
                    previous channel and default to 24 hours later.
                </p>
            </form>

            <div v-else-if="schedules.length" class="space-y-4">
                <div
                    v-for="schedule in schedules"
                    :key="schedule.id"
                    class="space-y-3 rounded-lg border p-4"
                >
                    <h3 class="text-sm font-semibold">
                        {{ attemptLabel(schedule.attempt_count - 1) }}
                    </h3>
                    <dl class="grid gap-4 text-sm md:grid-cols-2">
                        <div class="space-y-1">
                            <dt class="text-muted-foreground">Scheduled at</dt>
                            <dd>{{ formatDateTime(schedule.scheduled_at) }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-muted-foreground">Channel</dt>
                            <dd>{{ channelLabel(schedule.channel) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No schedule has been saved yet.
            </p>
        </CardContent>
    </Card>
</template>
