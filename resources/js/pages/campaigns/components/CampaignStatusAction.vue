<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Rocket, Square } from '@lucide/vue';
import { useNow } from '@vueuse/core';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import {
    launch,
    stop,
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
const isDraft = computed(
    () => props.campaign.status.key === CAMPAIGN_STATUS_KEY.draft,
);

function canStopAt(time: number): boolean {
    return (
        props.campaign.status.key === CAMPAIGN_STATUS_KEY.launched &&
        props.campaign.scheduled_at !== null &&
        Date.parse(props.campaign.scheduled_at) > time
    );
}

const canStop = computed(() => canStopAt(now.value.getTime()));

function handleAction() {
    if (
        !canEdit.value ||
        form.processing ||
        (!isDraft.value && !canStopAt(Date.now()))
    ) {
        return;
    }

    const message = isDraft.value
        ? `Launch "${props.campaign.name}"?`
        : `Stop "${props.campaign.name}" and return it to Draft?`;

    if (!window.confirm(message)) {
        return;
    }

    if (!isDraft.value && !canStopAt(Date.now())) {
        toast.error(
            'The campaign can only be stopped before its first scheduled time.',
        );

        return;
    }

    const action = isDraft.value ? launch : stop;
    form.submit(action(props.campaign.id), {
        preserveScroll: true,
        onError: (errors) => {
            toast.error('Campaign status was not changed.', {
                description: Object.values(errors).join(' '),
            });
        },
    });
}
</script>

<template>
    <Button
        v-if="canEdit && (isDraft || canStop)"
        type="button"
        :disabled="form.processing"
        :variant="canStop ? 'outline' : 'default'"
        @click="handleAction"
    >
        <component
            :is="isDraft ? Rocket : Square"
            class="size-4"
            aria-hidden="true"
        />
        {{ isDraft ? 'Launch' : 'Stop' }}
    </Button>
</template>
