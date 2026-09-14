<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useDateTimeFormat } from '@/composables/useDateTimeFormat';
import { CAMPAIGN_TYPE_KEY } from '../types';
import type { Campaign } from '../types';

defineProps<{
    campaign: Campaign;
    sidebar?: boolean;
}>();

const { formatDateTime } = useDateTimeFormat();
</script>

<template>
    <Card class="gap-3 py-4">
        <CardHeader class="px-4">
            <CardTitle>Basic details</CardTitle>
            <CardDescription class="break-words whitespace-pre-wrap">
                {{ campaign.short_note || 'No short note' }}
            </CardDescription>
        </CardHeader>
        <CardContent class="px-4">
            <dl
                class="grid gap-x-6 text-sm"
                :class="
                    sidebar
                        ? 'gap-y-5'
                        : 'gap-y-2 sm:grid-cols-2 lg:grid-cols-4'
                "
            >
                <div class="min-w-0">
                    <dt class="text-xs text-muted-foreground">Campaign ID</dt>
                    <dd
                        class="font-medium"
                        :class="sidebar ? 'break-all' : 'truncate'"
                        :title="campaign.id"
                    >
                        {{ campaign.id }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Campaign type</dt>
                    <dd class="font-medium">{{ campaign.type.label }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">
                        Campaign status
                    </dt>
                    <dd class="font-medium">{{ campaign.status.label }}</dd>
                </div>
                <div v-if="campaign.type.key === CAMPAIGN_TYPE_KEY.onceOff">
                    <dt class="text-xs text-muted-foreground">Scheduled At</dt>
                    <dd class="font-medium">
                        {{ formatDateTime(campaign.scheduled_at) || '--' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Created</dt>
                    <dd class="font-medium">
                        {{ formatDateTime(campaign.created_at) || '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Updated</dt>
                    <dd class="font-medium">
                        {{ formatDateTime(campaign.updated_at) || '-' }}
                    </dd>
                </div>
            </dl>
        </CardContent>
    </Card>
</template>
