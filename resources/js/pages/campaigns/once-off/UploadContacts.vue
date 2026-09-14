<script setup lang="ts">
import { Head, Link, useForm, useHttp } from '@inertiajs/vue3';
import { ChevronDown, Eye, FileUp, Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { index as campaignsIndex } from '@/actions/App/Http/Controllers/CampaignController';
import { store as storeContact } from '@/actions/App/Http/Controllers/OnceOffCampaignContactController';
import {
    index,
    store as storeImport,
    preview,
    show as showUpload,
} from '@/actions/App/Http/Controllers/OnceOffCampaignContactImportController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
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
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
import CampaignReadOnlyNotice from '../components/CampaignReadOnlyNotice.vue';
import {
    CAMPAIGN_STATUS_KEY,
    CONTACT_IMPORT_STATUS_KEY,
    CONTACT_UPLOAD_MODE_KEY,
} from '../types';
import type {
    Campaign,
    ContactFilePreview,
    ContactImportStatus,
    ContactUploadMode,
    OnceOffCampaignContactImport,
} from '../types';
import FilePreviewDialog from './FilePreviewDialog.vue';

const props = defineProps<{
    campaign: Campaign;
    contactImports: DataTableData<OnceOffCampaignContactImport>;
    importStatuses: ContactImportStatus[];
    uploadModes: ContactUploadMode[];
}>();
const { hasPermissions } = usePermissions();
const canEdit = computed(
    () =>
        hasPermissions(['campaigns.view', 'campaigns.edit'], true) &&
        props.campaign.status.key === CAMPAIGN_STATUS_KEY.draft,
);
defineOptions({
    layout: ({ campaign }: { campaign: Campaign }) => ({
        breadcrumbs: [
            { title: 'Campaigns', href: campaignsIndex() },
            { title: campaign.name, href: show(campaign.id) },
            { title: 'Upload Contacts', href: index(campaign.id) },
        ],
    }),
});
const uploadExpanded = ref(false);
const method = ref<'manual' | 'file'>('manual');
const fileInput = ref<HTMLInputElement | null>(null);
const selectedMode = ref(
    String(
        props.uploadModes.find(
            (mode) => mode.key === CONTACT_UPLOAD_MODE_KEY.append,
        )!.value,
    ),
);
const availableModes = computed(() =>
    props.uploadModes.filter(
        (mode) =>
            mode.key !== CONTACT_UPLOAD_MODE_KEY.replace ||
            (method.value === 'file' &&
                props.campaign.status.key === CAMPAIGN_STATUS_KEY.draft),
    ),
);
watch(availableModes, (modes) => {
    if (!modes.some((mode) => String(mode.value) === selectedMode.value)) {
        selectedMode.value = String(modes[0].value);
    }
});
const contactForm = useForm({
    first_name: '',
    last_name: '',
    number: '',
    email: '',
    mode: Number(selectedMode.value),
});
const uploadForm = useForm<{ file: File | null; mode: number }>({
    file: null,
    mode: Number(selectedMode.value),
});
const previewRequest = useHttp<
    { file: File | null; mode: number },
    ContactFilePreview
>({ file: null, mode: Number(selectedMode.value) });
const fileBusy = computed(
    () => uploadForm.processing || previewRequest.processing,
);
const filePreview = ref<ContactFilePreview | null>(null);
const previewOpen = ref(false);
const previewError = ref('');
const columns: DataTableColumn<OnceOffCampaignContactImport>[] = [
    { key: 'file_name', label: 'Upload', cellClass: 'font-medium' },
    { key: 'source', label: 'Source', sortable: false },
    { key: 'mode', label: 'Mode', sortable: false },
    { key: 'contact_count', label: 'Contacts' },
    { key: 'processed_count', label: 'Processed', sortable: false },
    { key: 'status', label: 'Status' },
    { key: 'uploaded_by', label: 'Uploaded by', sortable: false },
    { key: 'created_at', label: 'Uploaded at', format: 'datetime' },
    {
        key: 'actions',
        label: 'Actions',
        sortable: false,
        searchable: false,
        headerClass: 'w-32',
    },
];
function addContact() {
    if (!canEdit.value || contactForm.processing) {
        return;
    }

    contactForm.mode = Number(selectedMode.value);
    contactForm.post(storeContact.url(props.campaign.id), {
        preserveScroll: true,
        onSuccess: () => contactForm.reset(),
    });
}
function selectFile(event: Event) {
    if (!canEdit.value) {
        return;
    }

    uploadForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
    uploadForm.clearErrors();
    previewRequest.clearErrors();
    previewError.value = '';
    filePreview.value = null;
}
async function uploadFile() {
    if (!canEdit.value || fileBusy.value || !uploadForm.file) {
        return;
    }

    previewError.value = '';
    uploadForm.clearErrors();
    previewRequest.clearErrors();
    filePreview.value = null;
    previewOpen.value = false;
    previewRequest.file = uploadForm.file;
    previewRequest.mode = Number(selectedMode.value);
    uploadForm.mode = previewRequest.mode;

    try {
        const result = await previewRequest.post(
            preview.url(props.campaign.id),
        );

        if (result.error_count > 0) {
            filePreview.value = result;
            previewOpen.value = true;

            return;
        }

        if (!canEdit.value) {
            return;
        }

        uploadForm.post(storeImport.url(props.campaign.id), {
            preserveScroll: true,
        });
    } catch {
        if (previewRequest.hasErrors) {
            const errors = Object.values(previewRequest.errors).flat();
            filePreview.value = {
                headers: [],
                columns: [],
                rows: [],
                errors,
                error_count: errors.length,
            };
            previewOpen.value = true;
        } else {
            previewError.value =
                'Unable to validate the file. Please try again.';
        }
    }
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
    <Head :title="`Upload Contacts: ${campaign.name}`" />
    <div
        v-if="hasPermissions(['campaigns.view', 'campaigns.edit'], true)"
        class="space-y-6"
    >
        <CampaignReadOnlyNotice :status="campaign.status">
            You can review uploaded contacts, but uploading and syncing are
            allowed only while the campaign is draft.
        </CampaignReadOnlyNotice>
        <Collapsible v-if="canEdit" v-model:open="uploadExpanded" as-child>
            <Card class="gap-0">
                <CardHeader>
                    <CardTitle>
                        <CollapsibleTrigger
                            class="flex w-full items-center justify-between gap-4 rounded-sm text-left outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            Upload Contacts
                            <ChevronDown
                                class="size-5 shrink-0 text-muted-foreground transition-transform motion-reduce:transition-none"
                                :class="{ 'rotate-180': uploadExpanded }"
                                aria-hidden="true"
                            />
                        </CollapsibleTrigger>
                    </CardTitle>
                    <CardDescription
                        >Add a contact manually, or upload a file and sync it
                        when ready.</CardDescription
                    >
                </CardHeader>
                <CollapsibleContent v-show="uploadExpanded" force-mount>
                    <CardContent class="space-y-6 pt-6">
                        <div class="grid max-w-lg gap-2">
                            <Label for="upload-mode"
                                >When syncing this upload</Label
                            >
                            <Select v-model="selectedMode" :disabled="!canEdit"
                                ><SelectTrigger id="upload-mode"
                                    ><SelectValue /></SelectTrigger
                                ><SelectContent
                                    ><SelectItem
                                        v-for="mode in availableModes"
                                        :key="mode.key"
                                        :value="String(mode.value)"
                                        >{{ mode.label }}</SelectItem
                                    ></SelectContent
                                ></Select
                            >
                            <p class="text-sm text-muted-foreground">
                                Matching uses the phone number within this
                                campaign. Blank optional fields preserve saved
                                values when updating.
                            </p>
                            <InputError
                                :message="
                                    contactForm.errors.mode ||
                                    uploadForm.errors.mode ||
                                    previewRequest.errors.mode
                                "
                            />
                        </div>
                        <div
                            class="flex flex-wrap gap-2"
                            aria-label="Contact upload method"
                        >
                            <Button
                                type="button"
                                :variant="
                                    method === 'manual' ? 'default' : 'outline'
                                "
                                :aria-pressed="method === 'manual'"
                                @click="method = 'manual'"
                            >
                                <Plus class="size-4" />Manual entry
                            </Button>
                            <Button
                                type="button"
                                :variant="
                                    method === 'file' ? 'default' : 'outline'
                                "
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
                                        :readonly="!canEdit"
                                        maxlength="255"
                                        aria-required="true"
                                        autocomplete="given-name"
                                        :aria-invalid="
                                            Boolean(
                                                contactForm.errors.first_name,
                                            )
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
                                        :readonly="!canEdit"
                                        maxlength="255"
                                        autocomplete="family-name"
                                        :aria-invalid="
                                            Boolean(
                                                contactForm.errors.last_name,
                                            )
                                        "
                                    />
                                    <InputError
                                        :message="contactForm.errors.last_name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="contact-number" required
                                        >Number</Label
                                    >
                                    <Input
                                        id="contact-number"
                                        v-model="contactForm.number"
                                        :readonly="!canEdit"
                                        type="tel"
                                        maxlength="32"
                                        aria-required="true"
                                        autocomplete="tel"
                                        placeholder="e.g. +919876543210"
                                        :aria-invalid="
                                            Boolean(contactForm.errors.number)
                                        "
                                    />
                                    <InputError
                                        :message="contactForm.errors.number"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="contact-email"
                                        >Email (optional)</Label
                                    >
                                    <Input
                                        id="contact-email"
                                        v-model="contactForm.email"
                                        :readonly="!canEdit"
                                        type="email"
                                        maxlength="255"
                                        autocomplete="email"
                                        :aria-invalid="
                                            Boolean(contactForm.errors.email)
                                        "
                                    />
                                    <InputError
                                        :message="contactForm.errors.email"
                                    />
                                </div>
                            </div>
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <p class="text-sm text-muted-foreground">
                                    Manual entries stay pending until you sync
                                    them. Include + and the country code in the
                                    number.
                                </p>
                                <Button
                                    v-if="canEdit"
                                    type="submit"
                                    :disabled="contactForm.processing"
                                >
                                    <Spinner
                                        v-if="contactForm.processing"
                                    />Save to Uploaded Contacts
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
                                    <strong>last_name</strong>,
                                    <strong>email</strong>.
                                </p>
                                <p class="text-muted-foreground">
                                    CSV, XLSX, or XLS · Maximum 2 MB and 5,000
                                    contacts. Include + and the country code in
                                    every number. Only the first Excel worksheet
                                    is imported. Format phone numbers as text to
                                    preserve leading zeros and country codes.
                                    Use plain values instead of formulas.
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
                                <Label for="contact-file" required
                                    >Contact file</Label
                                >
                                <input
                                    id="contact-file"
                                    ref="fileInput"
                                    type="file"
                                    accept=".csv,.xlsx,.xls"
                                    aria-required="true"
                                    :disabled="!canEdit || fileBusy"
                                    :aria-invalid="
                                        Boolean(uploadForm.errors.file)
                                    "
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm file:mr-4 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1 file:text-foreground"
                                    @change="selectFile"
                                />
                                <InputError
                                    :message="
                                        previewRequest.errors.file ||
                                        uploadForm.errors.file ||
                                        previewError
                                    "
                                />
                            </div>
                            <p
                                v-if="previewRequest.progress"
                                class="text-sm text-muted-foreground"
                                role="status"
                            >
                                Uploading:
                                {{ previewRequest.progress.percentage }}%
                            </p>
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <p class="text-sm text-muted-foreground">
                                    Valid files are saved to Uploaded Contacts.
                                    Any errors will be shown for correction.
                                </p>
                                <Button
                                    v-if="canEdit"
                                    type="submit"
                                    :disabled="!uploadForm.file || fileBusy"
                                >
                                    <Spinner v-if="fileBusy" />Upload file
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>

        <section class="space-y-3" aria-label="Uploaded Contacts">
            <div>
                <h2 class="text-lg font-semibold">Uploaded Contacts</h2>
                <p class="text-sm text-muted-foreground">
                    Every manual entry and file is saved here first. Open an
                    upload to review and sync its contacts.
                </p>
            </div>

            <DataTable
                :data="contactImports"
                :columns="columns"
                prop-name="contactImports"
                caption="Uploaded Contacts"
                empty-message="No imports found."
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
                <template #cell-source="{ row }"
                    ><Badge variant="outline">{{
                        row.source.label
                    }}</Badge></template
                >
                <template #cell-mode="{ row }">{{ row.mode.label }}</template>
                <template #cell-processed_count="{ row }"
                    >{{ row.processed_count }} /
                    {{ row.contact_count }}</template
                >
                <template #cell-actions="{ row }"
                    ><Button variant="outline" size="sm" as-child
                        ><Link
                            :href="
                                showUpload.url({
                                    campaign: campaign.id,
                                    contactImport: row.id,
                                })
                            "
                            ><Eye class="size-4" />View</Link
                        ></Button
                    ></template
                >
            </DataTable>
        </section>
        <FilePreviewDialog v-model:open="previewOpen" :preview="filePreview" />
    </div>
</template>
