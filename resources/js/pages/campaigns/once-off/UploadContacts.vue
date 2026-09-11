<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileUp, Plus, RefreshCw } from '@lucide/vue';
import { ref } from 'vue';
import { store as storeContact } from '@/actions/App/Http/Controllers/OnceOffCampaignContactController';
import {
    store as storeImport,
    sync,
} from '@/actions/App/Http/Controllers/OnceOffCampaignContactImportController';
import DataTable from '@/components/data-table/DataTable.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/composables/usePermissions';
import type { DataTableColumn, DataTableData } from '@/types/data-table';
import { CONTACT_IMPORT_STATUS_KEY } from '../types';
import type {
    Campaign,
    ContactImportStatus,
    OnceOffCampaignContactImport,
} from '../types';

const props = defineProps<{
    campaign: Campaign;
    contactImports: DataTableData<OnceOffCampaignContactImport>;
    importStatuses: ContactImportStatus[];
}>();

const { hasPermissions } = usePermissions();
const method = ref<'manual' | 'file'>('manual');
const fileInput = ref<HTMLInputElement | null>(null);
const syncingId = ref<string | null>(null);
const contactForm = useForm({
    first_name: '',
    last_name: '',
    number: '',
    email: '',
});
const uploadForm = useForm<{ file: File | null }>({ file: null });
const syncForm = useForm<{ sync?: string }>({});

const columns: DataTableColumn<OnceOffCampaignContactImport>[] = [
    { key: 'file_name', label: 'File', cellClass: 'font-medium' },
    { key: 'contact_count', label: 'Contacts' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Uploaded at', format: 'datetime' },
    { key: 'synced_at', label: 'Synced at', format: 'datetime' },
    {
        key: 'actions',
        label: 'Actions',
        sortable: false,
        searchable: false,
        headerClass: 'w-32 text-right',
    },
];

function addContact() {
    if (
        !hasPermissions(['campaigns.view', 'campaigns.edit'], true) ||
        contactForm.processing
    ) {
        return;
    }

    contactForm.post(storeContact.url(props.campaign.id), {
        preserveScroll: true,
        onSuccess: () => contactForm.reset(),
    });
}

function selectFile(event: Event) {
    uploadForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
    uploadForm.clearErrors();
}

function uploadFile() {
    if (
        !hasPermissions(['campaigns.view', 'campaigns.edit'], true) ||
        uploadForm.processing
    ) {
        return;
    }

    uploadForm.post(storeImport.url(props.campaign.id), {
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

function syncImport(contactImport: OnceOffCampaignContactImport) {
    if (
        !hasPermissions(['campaigns.view', 'campaigns.edit'], true) ||
        syncForm.processing ||
        contactImport.status.key !== CONTACT_IMPORT_STATUS_KEY.pending
    ) {
        return;
    }

    syncingId.value = contactImport.id;
    syncForm.clearErrors();
    syncForm.post(
        sync.url({
            campaign: props.campaign.id,
            contactImport: contactImport.id,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                syncingId.value = null;
            },
        },
    );
}

function downloadTemplate() {
    if (!hasPermissions(['campaigns.view', 'campaigns.edit'], true)) {
        return;
    }

    const url = URL.createObjectURL(
        new Blob(['first_name,last_name,number,email\r\n'], {
            type: 'text/csv;charset=utf-8;',
        }),
    );
    const link = document.createElement('a');
    link.href = url;
    link.download = 'campaign-contacts-template.csv';
    link.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div
        v-if="hasPermissions(['campaigns.view', 'campaigns.edit'], true)"
        class="space-y-6"
    >
        <Card>
            <CardHeader>
                <CardTitle>Upload Contacts</CardTitle>
                <CardDescription
                    >Add a contact manually, or upload a file and sync it when
                    ready.</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-6">
                <div
                    class="flex flex-wrap gap-2"
                    aria-label="Contact upload method"
                >
                    <Button
                        type="button"
                        :variant="method === 'manual' ? 'default' : 'outline'"
                        :aria-pressed="method === 'manual'"
                        @click="method = 'manual'"
                    >
                        <Plus class="size-4" />Manual entry
                    </Button>
                    <Button
                        type="button"
                        :variant="method === 'file' ? 'default' : 'outline'"
                        :aria-pressed="method === 'file'"
                        @click="method = 'file'"
                    >
                        <FileUp class="size-4" />File upload
                    </Button>
                </div>

                <form
                    v-if="method === 'manual'"
                    class="space-y-6"
                    novalidate
                    @submit.prevent="addContact"
                >
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="contact-first-name" required
                                >First name</Label
                            >
                            <Input
                                id="contact-first-name"
                                v-model="contactForm.first_name"
                                maxlength="255"
                                aria-required="true"
                                autocomplete="given-name"
                                :aria-invalid="
                                    Boolean(contactForm.errors.first_name)
                                "
                            />
                            <InputError
                                :message="contactForm.errors.first_name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact-last-name"
                                >Last name (optional)</Label
                            >
                            <Input
                                id="contact-last-name"
                                v-model="contactForm.last_name"
                                maxlength="255"
                                autocomplete="family-name"
                                :aria-invalid="
                                    Boolean(contactForm.errors.last_name)
                                "
                            />
                            <InputError
                                :message="contactForm.errors.last_name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact-number" required>Number</Label>
                            <Input
                                id="contact-number"
                                v-model="contactForm.number"
                                type="tel"
                                maxlength="32"
                                aria-required="true"
                                autocomplete="tel"
                                placeholder="e.g. +919876543210"
                                :aria-invalid="
                                    Boolean(contactForm.errors.number)
                                "
                            />
                            <InputError :message="contactForm.errors.number" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact-email">Email (optional)</Label>
                            <Input
                                id="contact-email"
                                v-model="contactForm.email"
                                type="email"
                                maxlength="255"
                                autocomplete="email"
                                :aria-invalid="
                                    Boolean(contactForm.errors.email)
                                "
                            />
                            <InputError :message="contactForm.errors.email" />
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <p class="text-sm text-muted-foreground">
                            Manual entries are added to Contacts immediately.
                        </p>
                        <Button
                            type="submit"
                            :disabled="contactForm.processing"
                        >
                            <Spinner v-if="contactForm.processing" />Add contact
                        </Button>
                    </div>
                </form>

                <form
                    v-else
                    class="space-y-5"
                    novalidate
                    @submit.prevent="uploadFile"
                >
                    <div
                        class="space-y-2 rounded-lg border bg-muted/20 p-4 text-sm"
                    >
                        <p>
                            Use a header row with
                            <strong>first_name</strong> and
                            <strong>number</strong>. Optional columns:
                            <strong>last_name</strong>, <strong>email</strong>.
                        </p>
                        <p class="text-muted-foreground">
                            CSV, XLSX, or XLS · Maximum 2 MB and 5,000 contacts.
                            Only the first Excel worksheet is imported. Format
                            phone numbers as text to preserve leading zeros and
                            country codes. Use plain values instead of formulas.
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="downloadTemplate"
                            >Download CSV template</Button
                        >
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-file" required>Contact file</Label>
                        <input
                            id="contact-file"
                            ref="fileInput"
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            aria-required="true"
                            :disabled="uploadForm.processing"
                            :aria-invalid="Boolean(uploadForm.errors.file)"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm file:mr-4 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1 file:text-foreground"
                            @change="selectFile"
                        />
                        <InputError :message="uploadForm.errors.file" />
                    </div>
                    <p
                        v-if="uploadForm.progress"
                        class="text-sm text-muted-foreground"
                        role="status"
                    >
                        Uploading: {{ uploadForm.progress.percentage }}%
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <p class="text-sm text-muted-foreground">
                            Your file will stay pending until you click Sync
                            below.
                        </p>
                        <Button
                            type="submit"
                            :disabled="
                                !uploadForm.file || uploadForm.processing
                            "
                        >
                            <Spinner v-if="uploadForm.processing" />Upload to
                            pending list
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <section class="space-y-3" aria-label="Contact imports">
            <div>
                <h2 class="text-lg font-semibold">Contact imports</h2>
                <p class="text-sm text-muted-foreground">
                    Review pending files and sync them to add their contacts.
                    Filter by Synced to see completed imports.
                </p>
            </div>
            <InputError :message="syncForm.errors.sync" />
            <DataTable
                :data="contactImports"
                :columns="columns"
                prop-name="contactImports"
                caption="Contact imports"
                empty-message="No imports found."
                :reload-props="['contactSummary']"
            >
                <template #extra-filters="{ filters, loading }">
                    <Select
                        :model-value="String(filters.status ?? '__all')"
                        :disabled="loading"
                        @update:model-value="
                            filters.status =
                                $event === '__all' ? null : Number($event)
                        "
                    >
                        <SelectTrigger
                            class="w-44"
                            aria-label="Filter imports by status"
                            ><SelectValue placeholder="All statuses"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">All statuses</SelectItem>
                            <SelectItem
                                v-for="status in importStatuses"
                                :key="status.key"
                                :value="String(status.value)"
                                >{{ status.label }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </template>
                <template #cell-status="{ row }"
                    ><Badge
                        :variant="
                            row.status.key === CONTACT_IMPORT_STATUS_KEY.pending
                                ? 'outline'
                                : 'secondary'
                        "
                        >{{ row.status.label }}</Badge
                    ></template
                >
                <template #cell-actions="{ row }">
                    <div class="flex justify-end">
                        <Button
                            v-if="
                                row.status.key ===
                                CONTACT_IMPORT_STATUS_KEY.pending
                            "
                            type="button"
                            size="sm"
                            variant="outline"
                            :disabled="syncForm.processing"
                            @click="syncImport(row)"
                        >
                            <Spinner v-if="syncingId === row.id" /><RefreshCw
                                v-else
                                class="size-4"
                            />{{ syncingId === row.id ? 'Syncing…' : 'Sync' }}
                        </Button>
                    </div>
                </template>
            </DataTable>
        </section>
    </div>
</template>
