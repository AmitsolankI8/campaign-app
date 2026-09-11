<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarClock,
    ClipboardList,
    Pencil,
    Upload,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { edit, index } from '@/actions/App/Http/Controllers/CampaignController';
import { show } from '@/actions/App/Http/Controllers/OnceOffCampaignController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermissions } from '@/composables/usePermissions';
import type { DataTableData } from '@/types/data-table';
import CampaignBasicDetails from '../components/CampaignBasicDetails.vue';
import type {
    Campaign,
    ContactImportStatus,
    OnceOffCampaignContact,
    OnceOffCampaignContactImport,
} from '../types';
import Contacts from './Contacts.vue';
import UploadContacts from './UploadContacts.vue';

const props = defineProps<{
    campaign: Campaign;
    contacts: DataTableData<OnceOffCampaignContact>;
    contactImports: DataTableData<OnceOffCampaignContactImport> | null;
    contactSummary: { total: number; pending_imports: number };
    importStatuses: ContactImportStatus[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Campaigns',
                href: index(),
            },
            {
                title: 'Once-Off campaign',
                href: index(),
            },
        ],
    },
});

const { hasPermissions } = usePermissions();
const page = usePage();
const selectedTab = computed(() => {
    const tab = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');

    return tabs.value.some((item) => item.id === tab) ? tab : 'summary';
});

const tabs = computed(() =>
    [
        { id: 'summary', label: 'Summary', icon: ClipboardList },
        { id: 'contacts', label: 'Contacts', icon: Users },
        { id: 'upload_contacts', label: 'Upload Contacts', icon: Upload },
        { id: 'schedule', label: 'Schedule', icon: CalendarClock },
    ].filter(
        (tab) =>
            tab.id !== 'upload_contacts' || hasPermissions(['campaigns.edit']),
    ),
);

function selectTab(tab: string) {
    if (
        !hasPermissions(['campaigns.view']) ||
        !tabs.value.some((item) => item.id === tab)
    ) {
        return;
    }

    router.get(
        show.url(props.campaign.id, { query: { tab } }),
        {},
        { preserveState: true, preserveScroll: true },
    );
}
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
                    Campaign details and once-off campaign setup.
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

        <section class="space-y-4" aria-label="Campaign sections">
            <nav class="flex flex-wrap gap-2" aria-label="Campaign tabs">
                <Button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    :variant="selectedTab === tab.id ? 'default' : 'outline'"
                    :aria-pressed="selectedTab === tab.id"
                    @click="selectTab(tab.id)"
                >
                    <component :is="tab.icon" class="size-4" />
                    {{ tab.label }}
                </Button>
            </nav>

            <Card v-if="selectedTab === 'summary'">
                <CardHeader>
                    <CardTitle>Summary</CardTitle>
                    <CardDescription>
                        Campaign status at a glance.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-muted-foreground">
                            Contacts
                        </div>
                        <div class="mt-2 text-2xl font-semibold">
                            {{ contactSummary.total }}
                        </div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-muted-foreground">
                            Pending imports
                        </div>
                        <div class="mt-2 text-2xl font-semibold">
                            {{ contactSummary.pending_imports }}
                        </div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-muted-foreground">
                            Schedule
                        </div>
                        <div class="mt-2 text-2xl font-semibold">Not set</div>
                    </div>
                </CardContent>
            </Card>

            <Contacts
                v-else-if="selectedTab === 'contacts'"
                :contacts="contacts"
            />

            <UploadContacts
                v-else-if="
                    selectedTab === 'upload_contacts' &&
                    contactImports &&
                    hasPermissions(['campaigns.edit'])
                "
                :campaign="campaign"
                :contact-imports="contactImports"
                :import-statuses="importStatuses"
            />

            <Card v-else>
                <CardHeader>
                    <CardTitle>Schedule</CardTitle>
                    <CardDescription>Schedule is not set.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border p-4">
                            <div class="text-sm text-muted-foreground">
                                Start
                            </div>
                            <div class="mt-2 font-medium">Not selected</div>
                        </div>
                        <div class="rounded-lg border p-4">
                            <div class="text-sm text-muted-foreground">
                                Delivery window
                            </div>
                            <div class="mt-2 font-medium">Not selected</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
