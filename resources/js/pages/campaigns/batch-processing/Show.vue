<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { edit, index } from '@/actions/App/Http/Controllers/CampaignController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermissions } from '@/composables/usePermissions';
import CampaignBasicDetails from '../components/CampaignBasicDetails.vue';
import type { Campaign } from '../types';

defineProps<{
    campaign: Campaign;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: 'Batch processing campaign', href: index() },
        ],
    },
});

const { hasPermissions } = usePermissions();
</script>

<template>
    <Head :title="campaign.name" />

    <div
        v-if="hasPermissions(['campaigns.view'])"
        class="flex flex-1 flex-col gap-6 p-4"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ campaign.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Batch processing campaign flow.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="index.url()">Campaigns</Link>
                </Button>
                <Button v-if="hasPermissions(['campaigns.edit'])" as-child>
                    <Link :href="edit.url(campaign.id)">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <CampaignBasicDetails :campaign="campaign" />

        <Card>
            <CardHeader>
                <CardTitle>Batch Processing Flow</CardTitle>
                <CardDescription>
                    This campaign type will get its own setup flow.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    Batch processing configuration is pending.
                </div>
            </CardContent>
        </Card>
    </div>
</template>
