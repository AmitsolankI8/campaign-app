<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { create } from '@/actions/App/Http/Controllers/CampaignController';
import CampaignForm from './Form.vue';
import type { CampaignTypeOption } from './types';

defineProps<{
    campaignTypes: CampaignTypeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Create campaign',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Create campaign" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Create campaign
            </h1>
            <p class="text-sm text-muted-foreground">
                Create the campaign shell before adding contacts and schedule.
            </p>
        </div>

        <CampaignForm :campaign-types="campaignTypes" />
    </div>
</template>
