<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/CampaignController';
import CampaignForm from './Form.vue';
import type { Campaign } from './types';

defineProps<{
    campaign: Campaign;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Edit campaign',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit campaign" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit campaign</h1>
            <p class="text-sm text-muted-foreground">
                Update the campaign name and short note.
            </p>
        </div>

        <CampaignForm :campaign="campaign" />
    </div>
</template>
