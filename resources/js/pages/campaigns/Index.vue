<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Plus } from '@lucide/vue';
import { computed } from 'vue';
import {
    create,
    index,
} from '@/actions/App/Http/Controllers/CampaignController';
import DataTable from '@/components/data-table/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { DataTableColumn, DataTableData } from '@/types/data-table';
import type { CampaignRow, CampaignTypeOption } from './types';

defineProps<{
    campaigns: DataTableData<CampaignRow>;
    campaignTypes: CampaignTypeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Campaigns',
                href: index(),
            },
        ],
    },
});

const { hasPermissions } = usePermissions();

const columns = computed<DataTableColumn<CampaignRow>[]>(() => [
    { key: 'name', label: 'Name', cellClass: 'font-medium' },
    { key: 'type', label: 'Campaign type' },
    {
        key: 'short_note',
        label: 'Short note',
        cellClass: 'text-muted-foreground',
    },
    {
        key: 'created_at',
        label: 'Created At',
        format: 'datetime',
        cellClass: 'text-muted-foreground',
    },
    {
        key: 'actions',
        label: 'Actions',
        sortable: false,
        searchable: false,
        headerClass: 'w-32 text-right',
    },
]);
</script>

<template>
    <Head title="Campaigns" />

    <div
        v-if="hasPermissions(['campaigns.view'])"
        class="flex flex-1 flex-col gap-6 p-4"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Campaigns</h1>
                <p class="text-sm text-muted-foreground">
                    Manage campaign setup and once-off campaign flow.
                </p>
            </div>
            <Button v-if="hasPermissions(['campaigns.create'])" as-child>
                <Link :href="create.url()">
                    <Plus class="size-4" />
                    Create campaign
                </Link>
            </Button>
        </div>

        <DataTable
            :data="campaigns"
            :columns="columns"
            prop-name="campaigns"
            caption="Campaigns"
            empty-message="No campaigns found."
        >
            <template #extra-filters="{ filters, loading }">
                <Select
                    :model-value="String(filters.type ?? '__all')"
                    :disabled="loading"
                    @update:model-value="
                        filters.type =
                            $event === '__all' ? null : Number($event)
                    "
                >
                    <SelectTrigger
                        class="w-52"
                        aria-label="Filter by campaign type"
                    >
                        <SelectValue placeholder="All campaign types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all"
                            >All campaign types</SelectItem
                        >
                        <SelectItem
                            v-for="type in campaignTypes"
                            :key="type.key"
                            :value="String(type.value)"
                        >
                            {{ type.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>
            <template #cell-type="{ row: campaign }">
                <Badge variant="secondary">
                    {{ campaign.type.label }}
                </Badge>
            </template>
            <template #cell-actions="{ row: campaign }">
                <div class="flex justify-end">
                    <Button
                        v-if="hasPermissions(['campaigns.view'])"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <Link :href="campaign.show_url">
                            <Eye class="size-4" />
                            View
                        </Link>
                    </Button>
                </div>
            </template>
        </DataTable>
    </div>
</template>
