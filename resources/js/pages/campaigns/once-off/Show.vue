<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/CampaignController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
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
    contactSummary: { total: number; pending_imports: number };
}>();

defineOptions({
    layout: ({ campaign }: { campaign: Campaign }) => ({
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: campaign.name, href: show(campaign.id) },
        ],
    }),
});
</script>

<template>
    <Head :title="campaign.name" />

    <Card>
        <CardHeader>
            <CardTitle>Summary</CardTitle>
            <CardDescription> Campaign status at a glance. </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border p-4">
                <div class="text-sm text-muted-foreground">Contacts</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ contactSummary.total }}
                </div>
            </div>
            <div class="rounded-lg border p-4">
                <div class="text-sm text-muted-foreground">Pending imports</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ contactSummary.pending_imports }}
                </div>
            </div>
            <div class="rounded-lg border p-4">
                <div class="text-sm text-muted-foreground">Schedule</div>
                <div class="mt-2 text-2xl font-semibold">Not set</div>
            </div>
        </CardContent>
    </Card>
</template>
