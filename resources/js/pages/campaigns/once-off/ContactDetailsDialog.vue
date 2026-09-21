<script setup lang="ts">
import { Clock, MessageCircle, Send } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { useDateTimeFormat } from '@/composables/useDateTimeFormat';
import {
    COMMUNICATION_STATUS_KEY,
    COMMUNICATION_WORK_STATUS_KEY,
} from '@/types/communication';
import type {
    OnceOffCampaignContact,
    OnceOffCampaignContactDetails,
    OnceOffCampaignContactSchedule,
} from '../types';

const props = defineProps<{
    open: boolean;
    contact: OnceOffCampaignContact | null;
    details: OnceOffCampaignContactDetails | null;
    loading: boolean;
    error: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { formatDateTime } = useDateTimeFormat();
const selectedScheduleId = ref<string | null>(null);
const contactName = computed(() => {
    const contact = props.details ?? props.contact;

    return contact
        ? [contact.first_name, contact.last_name].filter(Boolean).join(' ')
        : 'Contact details';
});
const selectedSchedule = computed(
    () =>
        props.details?.schedules.find(
            (schedule) => schedule.id === selectedScheduleId.value,
        ) ?? null,
);
const selectedCommunication = computed(
    () => selectedSchedule.value?.communication ?? null,
);
const selectedProviderAttempts = computed(
    () => selectedCommunication.value?.provider_attempts ?? [],
);

watch(
    () => props.details,
    (details) => {
        selectedScheduleId.value = details?.schedules[0]?.id ?? null;
    },
    { immediate: true },
);

function scheduleLabel(scheduleNumber: number) {
    if (scheduleNumber === 1) {
        return 'First schedule';
    }

    if (scheduleNumber > 1) {
        return `Follow-up schedule ${scheduleNumber - 1}`;
    }

    return 'Schedule';
}

function statusVariant(status: string) {
    if (
        status === COMMUNICATION_STATUS_KEY.failed ||
        status === COMMUNICATION_STATUS_KEY.cancelled
    ) {
        return 'destructive' as const;
    }

    if (
        status === COMMUNICATION_STATUS_KEY.accepted ||
        status === COMMUNICATION_STATUS_KEY.sent ||
        status === COMMUNICATION_STATUS_KEY.delivered
    ) {
        return 'default' as const;
    }

    return 'secondary' as const;
}

function scheduleStatusVariant(status: string) {
    if (
        status === COMMUNICATION_WORK_STATUS_KEY.failed ||
        status === COMMUNICATION_WORK_STATUS_KEY.cancelled ||
        status === COMMUNICATION_WORK_STATUS_KEY.expired
    ) {
        return 'destructive' as const;
    }

    if (status === COMMUNICATION_WORK_STATUS_KEY.completed) {
        return 'default' as const;
    }

    return 'secondary' as const;
}

function selectSchedule(schedule: OnceOffCampaignContactSchedule) {
    selectedScheduleId.value = schedule.id;
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogScrollContent class="max-h-[92vh] overflow-y-auto sm:max-w-7xl">
            <DialogHeader class="pr-8">
                <DialogTitle>{{ contactName }}</DialogTitle>
                <DialogDescription>
                    Contact information and campaign communication history.
                </DialogDescription>
            </DialogHeader>

            <p
                v-if="error"
                class="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
                role="alert"
            >
                {{ error }}
            </p>

            <div v-else-if="loading" class="space-y-3">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Spinner /> Loading contact details...
                </div>
                <Skeleton class="h-28 w-full" />
                <div class="grid gap-4 lg:grid-cols-10">
                    <Skeleton class="h-96 lg:col-span-7" />
                    <Skeleton class="h-96 lg:col-span-3" />
                </div>
            </div>

            <div v-else-if="details" class="space-y-4">
                <Card class="gap-0 overflow-hidden py-0">
                    <CardHeader
                        class="grid-cols-[1fr_auto] grid-rows-1 items-center gap-3 border-b bg-muted/20 px-4 py-3"
                    >
                        <CardTitle class="text-base">Basic details</CardTitle>
                        <Badge variant="secondary">
                            {{ details.schedule_count }}
                            {{
                                details.schedule_count === 1
                                    ? 'schedule'
                                    : 'schedules'
                            }}
                        </Badge>
                    </CardHeader>
                    <CardContent
                        class="grid gap-x-6 gap-y-3 p-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Full name
                            </p>
                            <p class="truncate text-sm font-medium">
                                {{ contactName }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Phone number
                            </p>
                            <p class="truncate text-sm font-medium">
                                {{ details.number }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Email address
                            </p>
                            <p class="truncate text-sm font-medium">
                                {{ details.email ?? 'Not provided' }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Added at
                            </p>
                            <p class="truncate text-sm font-medium">
                                {{
                                    formatDateTime(details.created_at) ??
                                    'Unavailable'
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="grid items-stretch gap-4 lg:grid-cols-10">
                    <Card
                        class="min-h-[30rem] gap-0 overflow-hidden py-0 lg:col-span-7"
                    >
                        <CardHeader class="border-b bg-muted/20 px-4 py-3">
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <CardTitle class="text-base">
                                        Conversation
                                    </CardTitle>
                                    <CardDescription v-if="selectedSchedule">
                                        {{
                                            scheduleLabel(
                                                selectedSchedule.schedule_number,
                                            )
                                        }}
                                        via {{ selectedSchedule.channel.label }}
                                    </CardDescription>
                                    <CardDescription v-else>
                                        Select a schedule to review its
                                        activity.
                                    </CardDescription>
                                </div>
                                <Badge
                                    v-if="selectedCommunication"
                                    :variant="
                                        statusVariant(
                                            selectedCommunication.status.key,
                                        )
                                    "
                                >
                                    {{ selectedCommunication.status.label }}
                                </Badge>
                                <Badge
                                    v-else-if="selectedSchedule"
                                    :variant="
                                        scheduleStatusVariant(
                                            selectedSchedule.status.key,
                                        )
                                    "
                                >
                                    {{ selectedSchedule.status.label }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="flex min-h-[24rem] flex-col bg-muted/10 p-4"
                        >
                            <template v-if="selectedSchedule">
                                <div
                                    class="flex items-center gap-3 text-xs text-muted-foreground"
                                >
                                    <span class="h-px flex-1 bg-border" />
                                    <span>
                                        Scheduled
                                        {{
                                            formatDateTime(
                                                selectedSchedule.scheduled_at,
                                            ) ?? '—'
                                        }}
                                    </span>
                                    <span class="h-px flex-1 bg-border" />
                                </div>

                                <div
                                    v-if="selectedCommunication"
                                    class="mt-6 flex max-w-[80%] items-start self-end"
                                >
                                    <div
                                        class="rounded-2xl rounded-tr-sm bg-primary px-4 py-3 text-primary-foreground shadow-sm"
                                    >
                                        <div
                                            class="mb-1 flex items-center gap-2 text-xs font-medium opacity-80"
                                        >
                                            <Send class="size-3.5" />
                                            Campaign message
                                        </div>
                                        <p class="text-sm leading-relaxed">
                                            Message content is not stored with
                                            this scheduled communication.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="m-auto flex max-w-sm flex-col items-center gap-3 text-center"
                                >
                                    <div
                                        class="rounded-full bg-muted p-4 text-muted-foreground"
                                    >
                                        <MessageCircle class="size-7" />
                                    </div>
                                    <div>
                                        <p class="font-medium">
                                            No communication created
                                        </p>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            This schedule has not created a
                                            communication for this contact yet.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <div
                                v-else
                                class="m-auto flex max-w-sm flex-col items-center gap-3 text-center"
                            >
                                <div
                                    class="rounded-full bg-muted p-4 text-muted-foreground"
                                >
                                    <MessageCircle class="size-7" />
                                </div>
                                <div>
                                    <p class="font-medium">
                                        No conversation available
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        Communication activity will appear here
                                        after a schedule runs.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="min-h-[30rem] gap-0 overflow-hidden py-0 lg:col-span-3"
                    >
                        <CardHeader class="border-b bg-muted/20 px-4 py-3">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <CardTitle class="text-base">
                                        Schedules
                                    </CardTitle>
                                    <CardDescription>
                                        Campaign schedule order.
                                    </CardDescription>
                                </div>
                                <Badge variant="secondary">
                                    {{ details.schedule_count }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="flex min-h-0 flex-1 flex-col p-0">
                            <div
                                v-if="details.schedules.length"
                                class="max-h-64 space-y-2 overflow-y-auto p-2.5"
                            >
                                <button
                                    v-for="schedule in details.schedules"
                                    :key="schedule.id"
                                    type="button"
                                    class="w-full rounded-lg border p-2.5 text-left transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    :class="
                                        selectedScheduleId === schedule.id
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                            : 'bg-background'
                                    "
                                    :aria-pressed="
                                        selectedScheduleId === schedule.id
                                    "
                                    @click="selectSchedule(schedule)"
                                >
                                    <div
                                        class="grid grid-cols-[1fr_auto] grid-rows-1 items-center gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-medium"
                                            >
                                                {{
                                                    scheduleLabel(
                                                        schedule.schedule_number,
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{ schedule.channel.label }} ·
                                                {{
                                                    schedule.communication?.type
                                                        .label ??
                                                    'Campaign schedule'
                                                }}
                                            </p>
                                        </div>
                                        <Badge
                                            :variant="
                                                scheduleStatusVariant(
                                                    schedule.status.key,
                                                )
                                            "
                                            class="shrink-0"
                                        >
                                            {{ schedule.status.label }}
                                        </Badge>
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between gap-2 text-xs text-muted-foreground"
                                    >
                                        <span
                                            class="flex min-w-0 items-center gap-1"
                                        >
                                            <Clock class="size-3.5 shrink-0" />
                                            <span class="truncate">
                                                {{
                                                    formatDateTime(
                                                        schedule.scheduled_at,
                                                    ) ?? '—'
                                                }}
                                            </span>
                                        </span>
                                        <span class="shrink-0">
                                            {{
                                                schedule.communication
                                                    ?.provider_attempts
                                                    .length ?? 0
                                            }}
                                            tries
                                        </span>
                                    </div>
                                </button>
                            </div>
                            <div
                                v-else
                                class="flex min-h-48 flex-col items-center justify-center gap-2 p-5 text-center"
                            >
                                <Send class="size-6 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">
                                        No schedules yet
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Schedules appear here as the campaign
                                        runs.
                                    </p>
                                </div>
                            </div>

                            <div class="border-t bg-muted/10 p-3">
                                <div
                                    class="mb-2 flex items-center justify-between gap-2"
                                >
                                    <h3 class="text-sm font-medium">
                                        Provider attempts
                                    </h3>
                                    <Badge variant="outline">
                                        {{ selectedProviderAttempts.length }}
                                    </Badge>
                                </div>

                                <div
                                    v-if="selectedProviderAttempts.length"
                                    class="max-h-56 space-y-2 overflow-y-auto"
                                >
                                    <div
                                        v-for="attempt in selectedProviderAttempts"
                                        :key="attempt.id"
                                        class="rounded-lg border bg-background p-2.5"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-xs font-medium"
                                                >
                                                    Attempt
                                                    {{ attempt.attempt_number }}
                                                    · {{ attempt.provider }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        formatDateTime(
                                                            attempt.started_at,
                                                        ) ?? '—'
                                                    }}
                                                </p>
                                            </div>
                                            <Badge
                                                :variant="
                                                    statusVariant(
                                                        attempt.status.key,
                                                    )
                                                "
                                                class="shrink-0"
                                            >
                                                {{ attempt.status.label }}
                                            </Badge>
                                        </div>
                                        <p
                                            v-if="attempt.error_code"
                                            class="mt-1 truncate text-xs text-destructive"
                                        >
                                            {{ attempt.error_code }}
                                            <template v-if="attempt.retryable">
                                                · retryable
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <p
                                    v-else
                                    class="rounded-lg border border-dashed bg-background p-3 text-center text-xs text-muted-foreground"
                                >
                                    No provider attempts for the selected
                                    schedule.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DialogScrollContent>
    </Dialog>
</template>
