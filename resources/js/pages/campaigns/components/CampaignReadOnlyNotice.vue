<script setup lang="ts">
import { Info } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CAMPAIGN_STATUS_KEY } from '../types';
import type { CampaignStatusPayload } from '../types';

defineProps<{ status: CampaignStatusPayload }>();
</script>

<template>
    <Alert
        v-if="status.key !== CAMPAIGN_STATUS_KEY.draft"
        class="border-yellow-300 bg-yellow-50 text-yellow-900 dark:border-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-100"
    >
        <Info aria-hidden="true" />
        <AlertTitle>Read-only campaign</AlertTitle>
        <AlertDescription class="text-yellow-800 dark:text-yellow-200">
            <slot />
        </AlertDescription>
    </Alert>
</template>
