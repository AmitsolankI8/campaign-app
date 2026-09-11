<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import type { DataTableColumn, DataTableData } from '@/types/data-table';
import type { OnceOffCampaignContact } from '../types';

defineProps<{ contacts: DataTableData<OnceOffCampaignContact> }>();

const columns: DataTableColumn<OnceOffCampaignContact>[] = [
    { key: 'first_name', label: 'First name', cellClass: 'font-medium' },
    { key: 'last_name', label: 'Last name' },
    { key: 'number', label: 'Number' },
    { key: 'email', label: 'Email' },
    { key: 'created_at', label: 'Added at', format: 'datetime' },
];
</script>

<template>
    <DataTable
        :data="contacts"
        :columns="columns"
        prop-name="contacts"
        caption="Campaign contacts"
        empty-message="No contacts found. Add contacts from the Upload Contacts tab."
    />
</template>
