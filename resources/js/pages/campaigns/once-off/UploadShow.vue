<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { index } from '@/actions/App/Http/Controllers/CampaignController';
import {
    index as uploadsIndex,
    show as showUpload,
    sync,
} from '@/actions/App/Http/Controllers/OnceOffCampaignContactImportController';
import { show as showCampaign } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import DataTable from '@/components/data-table/DataTable.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useDateTimeFormat } from '@/composables/useDateTimeFormat';
import { usePermissions } from '@/composables/usePermissions';
import type { DataTableColumn, DataTableData } from '@/types/data-table';
import CampaignReadOnlyNotice from '../components/CampaignReadOnlyNotice.vue';
import {
    CAMPAIGN_STATUS_KEY,
    CONTACT_IMPORT_STATUS_KEY,
    CONTACT_UPLOAD_MODE_KEY,
    CONTACT_UPLOAD_ROW_STATUS_KEY,
} from '../types';
import type {
    Campaign,
    ContactSyncPlan,
    ContactUploadRow,
    ContactUploadRowStatus,
    OnceOffCampaignContactImport,
} from '../types';

const props = defineProps<{
    campaign: Campaign;
    upload: OnceOffCampaignContactImport;
    rows: DataTableData<ContactUploadRow>;
    syncPlan: ContactSyncPlan | null;
    rowStatuses: ContactUploadRowStatus[];
}>();
defineOptions({
    layout: ({
        campaign,
        upload,
    }: {
        campaign: Campaign;
        upload: OnceOffCampaignContactImport;
    }) => ({
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: campaign.name, href: showCampaign(campaign.id) },
            {
                title: 'Uploaded contacts',
                href: uploadsIndex(campaign.id),
            },
            {
                title: upload.file_name,
                href: showUpload({
                    campaign: campaign.id,
                    contactImport: upload.id,
                }),
            },
        ],
    }),
});
const { hasPermissions } = usePermissions();
const { formatDateTime } = useDateTimeFormat();
const allowed = computed(() =>
    hasPermissions(['campaigns.view', 'campaigns.edit'], true),
);
const replacing = computed(
    () => props.upload.mode.key === CONTACT_UPLOAD_MODE_KEY.replace,
);
const completed = computed(
    () => props.upload.status.key === CONTACT_IMPORT_STATUS_KEY.synced,
);
const canSync = computed(
    () =>
        allowed.value &&
        !completed.value &&
        props.campaign.status.key === CAMPAIGN_STATUS_KEY.draft,
);
const selected = ref<string[]>([]);
const replacementOpen = ref(false);
const reviewedPlan = ref<ContactSyncPlan | null>(null);
const refreshing = ref(false);
const form = useForm<{
    row_ids?: string[];
    fingerprint?: string;
    sync?: string;
}>({});
const actionLabels = { add: 'Add', update: 'Update', skip: 'Skip duplicate' };
const fieldLabels: Record<string, string> = {
    first_name: 'First name',
    last_name: 'Last name',
    number: 'Number',
    email: 'Email',
};
const pendingRow = (row: ContactUploadRow) =>
    row.status.key === CONTACT_UPLOAD_ROW_STATUS_KEY.pending ||
    row.status.key === CONTACT_UPLOAD_ROW_STATUS_KEY.failed;
const selectableRows = computed(() => props.rows.data.filter(pendingRow));
watch(
    () => props.rows,
    () => {
        selected.value = [];
    },
);
const columns = computed<DataTableColumn<ContactUploadRow>[]>(() => [
    ...(canSync.value && !replacing.value
        ? [
              {
                  key: 'select',
                  label: 'Select',
                  sortable: false,
                  searchable: false,
              },
          ]
        : []),
    { key: 'row_number', label: 'File row' },
    { key: 'first_name', label: 'First name' },
    { key: 'last_name', label: 'Last name' },
    { key: 'number', label: 'Number' },
    { key: 'email', label: 'Email' },
    { key: 'status', label: 'Outcome' },
    {
        key: 'planned_action',
        label: 'Next action',
        sortable: false,
        searchable: false,
    },
    { key: 'synced_at', label: 'Synced at', format: 'datetime' },
    ...(canSync.value && !replacing.value
        ? [
              {
                  key: 'actions',
                  label: 'Actions',
                  sortable: false,
                  searchable: false,
              },
          ]
        : []),
]);
function selectPage() {
    if (!canSync.value || replacing.value || form.processing) {
        return;
    }

    selected.value =
        selected.value.length === selectableRows.value.length
            ? []
            : selectableRows.value.map((row) => row.id);
}
function submitSync(ids?: string[], fingerprint?: string) {
    if (
        !canSync.value ||
        form.processing ||
        (replacing.value && (ids || !fingerprint))
    ) {
        return;
    }

    form.clearErrors();
    form.transform(() => ({
        ...(ids ? { row_ids: ids } : {}),
        ...(fingerprint ? { fingerprint } : {}),
    })).post(
        sync.url({
            campaign: props.campaign.id,
            contactImport: props.upload.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
                replacementOpen.value = false;
            },
            onError: () => {
                replacementOpen.value = false;
                reviewedPlan.value = null;
            },
        },
    );
}
function confirmReplacement() {
    if (
        !canSync.value ||
        !props.syncPlan ||
        refreshing.value ||
        form.processing
    ) {
        return;
    }

    reviewedPlan.value = { ...props.syncPlan };
    replacementOpen.value = true;
}
function refreshPreview() {
    if (!allowed.value || refreshing.value || form.processing) {
        return;
    }

    refreshing.value = true;
    router.reload({
        only: ['campaign', 'upload', 'rows', 'syncPlan', 'auth'],
        onFinish: () => {
            refreshing.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Upload: ${upload.file_name}`" />
    <div v-if="allowed" class="min-w-0 space-y-4">
        <CampaignReadOnlyNotice :status="campaign.status">
            You can review this upload and download its original file, but
            syncing is allowed only while the campaign is draft.
        </CampaignReadOnlyNotice>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 space-y-1">
                <h2 class="text-xl font-semibold tracking-tight break-words">
                    {{ upload.file_name }}
                </h2>
                <p class="text-sm text-muted-foreground">Uploaded contacts</p>
            </div>
            <Button variant="outline" as-child
                ><Link :href="uploadsIndex.url(campaign.id)"
                    >Back to uploads</Link
                ></Button
            >
        </div>
        <Card>
            <CardHeader><CardTitle>Upload details</CardTitle></CardHeader>
            <CardContent
                class="grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4"
            >
                <div>
                    <p class="text-muted-foreground">Source</p>
                    <p class="mt-1 font-medium">{{ upload.source.label }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Mode</p>
                    <p class="mt-1 font-medium">{{ upload.mode.label }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Status</p>
                    <Badge class="mt-1" variant="secondary">{{
                        upload.status.label
                    }}</Badge>
                </div>
                <div>
                    <p class="text-muted-foreground">Processed</p>
                    <p class="mt-1 font-medium">
                        {{ upload.processed_count }} /
                        {{ upload.contact_count }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Uploaded by</p>
                    <p class="mt-1">
                        {{
                            upload.uploaded_by ??
                            'Unavailable for older uploads'
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Uploaded at</p>
                    <p class="mt-1">{{ formatDateTime(upload.created_at) }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Completed at</p>
                    <p class="mt-1">
                        {{
                            formatDateTime(upload.synced_at) ?? 'Not completed'
                        }}
                    </p>
                </div>
                <div v-if="upload.download_url">
                    <Button variant="outline" size="sm" as-child
                        ><a :href="upload.download_url"
                            >Download original file</a
                        ></Button
                    >
                </div>
                <div v-if="upload.removed_count">
                    <p class="text-muted-foreground">Removed from campaign</p>
                    <p class="mt-1 font-medium">
                        {{ upload.removed_count }} contacts
                    </p>
                </div>
            </CardContent>
        </Card>
        <p
            v-if="upload.history_count < upload.contact_count"
            class="rounded-md border bg-muted/30 p-4 text-sm text-muted-foreground"
        >
            Some row history was not retained by the previous upload system. The
            original totals are preserved.
        </p>
        <div v-if="!completed" class="space-y-4 rounded-lg border p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold">Sync preview</h2>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="refreshing || form.processing"
                    @click="refreshPreview"
                    ><Spinner v-if="refreshing" />Refresh preview</Button
                >
            </div>
            <p v-if="syncPlan" class="text-sm">
                {{ syncPlan.add }} to add · {{ syncPlan.update }} to update ·
                {{ syncPlan.skip }} to skip<template v-if="replacing">
                    · {{ syncPlan.remove }} to remove</template
                >
            </p>
            <p class="text-sm text-muted-foreground">
                Matches are checked again at sync time. Blank optional fields
                preserve existing values.
            </p>
            <p v-if="replacing" class="text-sm text-muted-foreground">
                Replacement applies the entire upload together. Contacts absent
                from this file will be removed from this campaign.
            </p>
            <div v-if="canSync" class="flex flex-wrap gap-2">
                <Button
                    v-if="replacing"
                    variant="destructive"
                    :disabled="form.processing || refreshing || !syncPlan"
                    @click="confirmReplacement"
                    >Replace entire list</Button
                >
                <template v-else>
                    <Button :disabled="form.processing" @click="submitSync()"
                        ><Spinner v-if="form.processing" />Sync all
                        remaining</Button
                    >
                    <Button
                        variant="outline"
                        :disabled="form.processing || !selected.length"
                        @click="submitSync(selected)"
                        >Sync selected ({{ selected.length }})</Button
                    >
                    <Button
                        variant="outline"
                        :disabled="form.processing || !selectableRows.length"
                        @click="selectPage"
                        >{{
                            selected.length === selectableRows.length &&
                            selected.length
                                ? 'Clear selection'
                                : 'Select pending on this page'
                        }}</Button
                    >
                </template>
            </div>
            <InputError
                :message="
                    form.errors.sync ||
                    form.errors.row_ids ||
                    form.errors.fingerprint
                "
            />
        </div>
        <DataTable
            :data="rows"
            :columns="columns"
            prop-name="rows"
            caption="Uploaded contacts"
            empty-message="No uploaded rows found."
            :show-row-numbers="false"
            :reload-props="['upload', 'syncPlan', 'campaign']"
        >
            <template #extra-filters="{ filters, loading }"
                ><Select
                    :model-value="String(filters.status ?? '__all')"
                    :disabled="loading"
                    @update:model-value="
                        filters.status =
                            $event === '__all' ? null : Number($event)
                    "
                    ><SelectTrigger
                        class="w-48"
                        aria-label="Filter row outcomes"
                        ><SelectValue /></SelectTrigger
                    ><SelectContent
                        ><SelectItem value="__all">All outcomes</SelectItem
                        ><SelectItem
                            v-for="status in rowStatuses"
                            :key="status.key"
                            :value="String(status.value)"
                            >{{ status.label }}</SelectItem
                        ></SelectContent
                    ></Select
                ></template
            >
            <template #cell-select="{ row }"
                ><input
                    v-if="pendingRow(row)"
                    v-model="selected"
                    type="checkbox"
                    :value="row.id"
                    :disabled="form.processing"
                    :aria-label="`Select row ${row.row_number}`"
            /></template>
            <template #cell-status="{ row }"
                ><div class="space-y-2">
                    <Badge :variant="row.error ? 'destructive' : 'secondary'">{{
                        row.status.label
                    }}</Badge>
                    <p
                        v-if="row.error"
                        class="max-w-72 text-xs text-destructive"
                    >
                        {{ row.error }}
                    </p>
                    <details v-if="row.before_values" class="text-xs">
                        <summary class="cursor-pointer text-muted-foreground">
                            Prior values
                        </summary>
                        <dl class="mt-2 space-y-1">
                            <div
                                v-for="(value, key) in row.before_values"
                                :key="key"
                            >
                                <dt class="font-medium">
                                    {{ fieldLabels[key] ?? key }}
                                </dt>
                                <dd>{{ value ?? '—' }}</dd>
                            </div>
                        </dl>
                    </details>
                </div></template
            >
            <template #cell-planned_action="{ row }">{{
                row.planned_action ? actionLabels[row.planned_action] : '—'
            }}</template>
            <template #cell-actions="{ row }"
                ><Button
                    v-if="pendingRow(row)"
                    variant="outline"
                    size="sm"
                    :disabled="form.processing"
                    @click="submitSync([row.id])"
                    >{{
                        row.status.key === CONTACT_UPLOAD_ROW_STATUS_KEY.failed
                            ? 'Retry'
                            : 'Sync'
                    }}</Button
                ></template
            >
        </DataTable>
        <Dialog v-model:open="replacementOpen"
            ><DialogContent
                ><DialogHeader
                    ><DialogTitle>Replace all campaign contacts?</DialogTitle
                    ><DialogDescription v-if="reviewedPlan"
                        >This will add {{ reviewedPlan.add }}, update
                        {{ reviewedPlan.update }}, and remove
                        {{ reviewedPlan.remove }} contacts from
                        {{ campaign.name }}. The entire change succeeds or
                        leaves the previous list intact.</DialogDescription
                    ></DialogHeader
                ><DialogFooter
                    ><Button
                        variant="outline"
                        :disabled="form.processing"
                        @click="replacementOpen = false"
                        >Cancel</Button
                    ><Button
                        variant="destructive"
                        :disabled="form.processing || !canSync"
                        @click="
                            submitSync(undefined, reviewedPlan?.fingerprint)
                        "
                        ><Spinner v-if="form.processing" />Confirm
                        replacement</Button
                    ></DialogFooter
                ></DialogContent
            ></Dialog
        >
    </div>
</template>
