<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/CampaignController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import { show as showSchedule } from '@/actions/App/Http/Controllers/OnceOffCampaignScheduleController';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Campaign } from '../types';

defineProps<{
    campaign: Campaign;
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
</script>

<template>
    <Head :title="`Schedule: ${campaign.name}`" />

    <Card>
        <CardHeader>
            <CardTitle>Schedule</CardTitle>
            <CardDescription>Schedule is not set.</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-muted-foreground">Start</div>
                    <div class="mt-2 font-medium">Not selected</div>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-muted-foreground">
                        Delivery window
                    </div>
                    <div class="mt-2 font-medium">Not selected</div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
