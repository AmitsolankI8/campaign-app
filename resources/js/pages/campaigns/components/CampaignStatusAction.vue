<script setup lang="ts">
import { Ban, Pause, Play, Rocket } from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { CAMPAIGN_STATUS_KEY } from '../types';
import type { CampaignStatusKey, CampaignStatusPayload } from '../types';

const props = defineProps<{ status: CampaignStatusPayload }>();
const { hasPermissions } = usePermissions();
const canEdit = computed(() =>
    hasPermissions(['campaigns.view', 'campaigns.edit'], true),
);

type StatusAction = {
    label: string;
    icon: Component;
    intent: 'launch' | 'pause' | 'resume' | null;
};

const statusActions: Record<CampaignStatusKey, StatusAction> = {
    [CAMPAIGN_STATUS_KEY.draft]: {
        label: 'Launch',
        icon: Rocket,
        intent: 'launch',
    },
    [CAMPAIGN_STATUS_KEY.launched]: {
        label: 'Pause',
        icon: Pause,
        intent: 'pause',
    },
    [CAMPAIGN_STATUS_KEY.running]: {
        label: 'Pause',
        icon: Pause,
        intent: 'pause',
    },
    [CAMPAIGN_STATUS_KEY.paused]: {
        label: 'Resume',
        icon: Play,
        intent: 'resume',
    },
    [CAMPAIGN_STATUS_KEY.cancelled]: {
        label: 'Cancelled',
        icon: Ban,
        intent: null,
    },
};

const action = computed(() => statusActions[props.status.key]);

function handleAction() {
    if (!canEdit.value || !action.value?.intent) {
        return;
    }

    // UI preview until campaign status transitions are implemented.
    toast.info(`${action.value.label} is not available yet.`, {
        description: 'The campaign status has not changed.',
    });
}
</script>

<template>
    <Button
        v-if="canEdit && action"
        type="button"
        :disabled="action.intent === null"
        :variant="action.intent === 'pause' ? 'outline' : 'default'"
        @click="handleAction"
    >
        <component :is="action.icon" class="size-4" aria-hidden="true" />
        {{ action.label }}
    </Button>
</template>
