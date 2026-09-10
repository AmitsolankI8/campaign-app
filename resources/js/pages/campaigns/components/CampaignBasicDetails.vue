<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useDateTimeFormat } from '@/composables/useDateTimeFormat';
import type { Campaign } from '../types';

withDefaults(
    defineProps<{
        campaign: Campaign;
        typeFlowLabel?: string;
    }>(),
    {
        typeFlowLabel: 'Pending',
    },
);

const { formatDateTime } = useDateTimeFormat();
</script>

<template>
    <Card class="gap-3 py-4">
        <CardHeader class="px-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <CardTitle>Basic details</CardTitle>
                    <CardDescription>
                        {{ campaign.short_note || 'No short note' }}
                    </CardDescription>
                </div>
                <Badge variant="secondary">
                    {{ campaign.type.label }}
                </Badge>
            </div>
        </CardHeader>
        <CardContent
            class="grid gap-2 px-4 text-xs sm:grid-cols-2 lg:grid-cols-4"
        >
            <div class="rounded-md border p-3">
                <div class="text-muted-foreground">Campaign ID</div>
                <div class="mt-0.5 text-sm font-medium break-all">
                    {{ campaign.id }}
                </div>
            </div>
            <div class="rounded-md border p-3">
                <div class="text-muted-foreground">Type flow</div>
                <div class="mt-0.5 text-sm font-medium">
                    {{ typeFlowLabel }}
                </div>
            </div>
            <div class="rounded-md border p-3">
                <div class="text-muted-foreground">Created</div>
                <div class="mt-0.5 text-sm font-medium">
                    {{ formatDateTime(campaign.created_at) || '-' }}
                </div>
            </div>
            <div class="rounded-md border p-3">
                <div class="text-muted-foreground">Updated</div>
                <div class="mt-0.5 text-sm font-medium">
                    {{ formatDateTime(campaign.updated_at) || '-' }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
