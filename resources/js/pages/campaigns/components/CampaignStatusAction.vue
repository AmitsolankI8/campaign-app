<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useNow } from '@vueuse/core';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import {
    launch,
    stop,
    pause,
    resume,
    cancel,
} from '@/actions/App/Http/Controllers/OnceOffCampaignStatusController';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { CAMPAIGN_STATUS_KEY } from '../types';
import type { Campaign } from '../types';

const props = defineProps<{ campaign: Campaign }>();
const { hasPermissions } = usePermissions();
const canEdit = computed(() =>
    hasPermissions(['campaigns.view', 'campaigns.edit'], true),
);
const form = useForm({});
const now = useNow({ interval: 1000 });
const actions = { launch, stop, pause, resume, cancel };
type Action = keyof typeof actions;
const labels: Record<Action, string> = {
    launch: 'Launch',
    stop: 'Stop',
    pause: 'Pause',
    resume: 'Resume',
    cancel: 'Cancel',
};

function allowed(action: Action, time: number): boolean {
    const status = props.campaign.status.key;

    switch (action) {
        case 'launch':
            return status === CAMPAIGN_STATUS_KEY.draft;
        case 'stop':
            return (
                status === CAMPAIGN_STATUS_KEY.launched &&
                props.campaign.scheduled_at !== null &&
                Date.parse(props.campaign.scheduled_at) > time
            );
        case 'pause':
            return (
                status === CAMPAIGN_STATUS_KEY.launched ||
                status === CAMPAIGN_STATUS_KEY.running
            );
        case 'resume':
            return status === CAMPAIGN_STATUS_KEY.paused;
        case 'cancel':
            return (
                status === CAMPAIGN_STATUS_KEY.launched ||
                status === CAMPAIGN_STATUS_KEY.running ||
                status === CAMPAIGN_STATUS_KEY.paused
            );
    }
}
const available = computed(() =>
    canEdit.value
        ? (Object.keys(actions) as Action[]).filter((action) =>
              allowed(action, now.value.getTime()),
          )
        : [],
);

function submit(action: Action) {
    if (!canEdit.value || form.processing || !allowed(action, Date.now())) {
        return;
    }

    const detail =
        action === 'cancel'
            ? ' This permanently cancels unsent work.'
            : action === 'stop'
              ? ' This returns the campaign to Draft.'
              : action === 'resume'
                ? ' Overdue work will run immediately.'
                : '';

    if (
        !window.confirm(`${labels[action]} "${props.campaign.name}"?${detail}`)
    ) {
        return;
    }

    if (!allowed(action, Date.now())) {
        return;
    }

    form.submit(actions[action](props.campaign.id), {
        preserveScroll: true,
        onError: (errors) =>
            toast.error('Campaign status was not changed.', {
                description: Object.values(errors).join(' '),
            }),
    });
}
</script>

<template>
    <div v-if="available.length" class="flex flex-wrap items-center gap-2">
        <Button
            v-for="action in available"
            :key="action"
            type="button"
            :disabled="form.processing"
            :variant="
                action === 'cancel'
                    ? 'destructive'
                    : action === 'launch'
                      ? 'default'
                      : 'outline'
            "
            @click="submit(action)"
            >{{ labels[action] }}</Button
        >
    </div>
</template>
