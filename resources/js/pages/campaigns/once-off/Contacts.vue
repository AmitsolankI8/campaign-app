<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index as campaignsIndex } from '@/actions/App/Http/Controllers/CampaignController';
import { index } from '@/actions/App/Http/Controllers/OnceOffCampaignContactController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import DataTable from '@/components/data-table/DataTable.vue';
import type { DataTableColumn, DataTableData } from '@/types/data-table';
import type { Campaign, OnceOffCampaignContact } from '../types';

defineProps<{
    campaign: Campaign;
    contacts: DataTableData<OnceOffCampaignContact>;
}>();

defineOptions({
    layout: ({ campaign }: { campaign: Campaign }) => ({
        breadcrumbs: [
            { title: 'Campaigns', href: campaignsIndex() },
            { title: campaign.name, href: show(campaign.id) },
            { title: 'Contacts', href: index(campaign.id) },
        ],
    }),
});

const columns: DataTableColumn<OnceOffCampaignContact>[] = [
    { key: 'first_name', label: 'First name', cellClass: 'font-medium' },
    { key: 'last_name', label: 'Last name' },
    { key: 'number', label: 'Number' },
    { key: 'email', label: 'Email' },
    { key: 'created_at', label: 'Added at', format: 'datetime' },
];
</script>

<template>
    <Head :title="`Contacts: ${campaign.name}`" />
    <DataTable
        :data="contacts"
        :columns="columns"
        prop-name="contacts"
        caption="Campaign contacts"
        empty-message="No contacts found. Add contacts from the Upload Contacts tab."
    />
</template>
