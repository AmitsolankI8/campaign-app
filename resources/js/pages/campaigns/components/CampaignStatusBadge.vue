<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { CAMPAIGN_STATUS_KEY } from '../types';
import type { CampaignStatusKey, CampaignStatusPayload } from '../types';

defineProps<{ status: CampaignStatusPayload }>();

const statusClasses: Record<CampaignStatusKey, string> = {
    [CAMPAIGN_STATUS_KEY.draft]:
        'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
    [CAMPAIGN_STATUS_KEY.launched]:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
    [CAMPAIGN_STATUS_KEY.running]:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    [CAMPAIGN_STATUS_KEY.paused]:
        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
    [CAMPAIGN_STATUS_KEY.completed]:
        'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-800 dark:bg-teal-950 dark:text-teal-300',
    [CAMPAIGN_STATUS_KEY.cancelled]:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
};
</script>

<template>
    <Badge
        variant="outline"
        class="gap-1.5 px-2.5 py-1"
        :class="statusClasses[status.key]"
        role="status"
    >
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ status.label }}
    </Badge>
</template>
