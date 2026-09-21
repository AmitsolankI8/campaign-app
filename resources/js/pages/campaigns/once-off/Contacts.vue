<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as campaignsIndex } from '@/actions/App/Http/Controllers/CampaignController';
import {
    index,
    show as showContact,
} from '@/actions/App/Http/Controllers/OnceOffCampaignContactController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import DataTable from '@/components/data-table/DataTable.vue';
import { Button } from '@/components/ui/button';
import type { DataTableColumn, DataTableData } from '@/types/data-table';
import type {
    Campaign,
    OnceOffCampaignContact,
    OnceOffCampaignContactDetails,
} from '../types';
import ContactDetailsDialog from './ContactDetailsDialog.vue';

const props = defineProps<{
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
    {
        key: 'actions',
        label: 'Actions',
        sortable: false,
        searchable: false,
        headerClass: 'w-28',
    },
];

const detailsOpen = ref(false);
const selectedContact = ref<OnceOffCampaignContact | null>(null);
const contactDetails = ref<OnceOffCampaignContactDetails | null>(null);
const detailsError = ref('');
const detailsRequest = useHttp<
    Record<string, never>,
    OnceOffCampaignContactDetails
>({});

async function viewContact(contact: OnceOffCampaignContact) {
    if (detailsRequest.processing) {
        return;
    }

    selectedContact.value = contact;
    contactDetails.value = null;
    detailsError.value = '';
    detailsOpen.value = true;

    try {
        contactDetails.value = await detailsRequest.get(
            showContact.url({
                campaign: props.campaign.id,
                contact: contact.id,
            }),
        );
    } catch {
        detailsError.value =
            'Unable to load contact details. Please try again.';
    }
}

watch(detailsOpen, (open) => {
    if (!open) {
        detailsRequest.cancel();
        selectedContact.value = null;
        contactDetails.value = null;
        detailsError.value = '';
    }
});
</script>

<template>
    <Head :title="`Contacts: ${campaign.name}`" />
    <DataTable
        :data="contacts"
        :columns="columns"
        prop-name="contacts"
        caption="Campaign contacts"
        empty-message="No contacts found. Add contacts from the Upload Contacts tab."
    >
        <template #cell-actions="{ row }">
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="detailsRequest.processing"
                @click="viewContact(row)"
            >
                <Eye class="size-4" />
                View
            </Button>
        </template>
    </DataTable>

    <ContactDetailsDialog
        v-model:open="detailsOpen"
        :contact="selectedContact"
        :details="contactDetails"
        :loading="detailsRequest.processing"
        :error="detailsError"
    />
</template>
