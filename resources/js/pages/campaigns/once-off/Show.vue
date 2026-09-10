<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarClock,
    ClipboardList,
    Pencil,
    Upload,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { edit, index } from '@/actions/App/Http/Controllers/CampaignController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermissions } from '@/composables/usePermissions';
import CampaignBasicDetails from '../components/CampaignBasicDetails.vue';
import type { Campaign } from '../types';

defineProps<{
    campaign: Campaign;
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
const selectedTab = ref('summary');

const tabs = [
    { id: 'summary', label: 'Summary', icon: ClipboardList },
    { id: 'contacts', label: 'Contacts', icon: Users },
    { id: 'upload_contacts', label: 'Upload Contacts', icon: Upload },
    { id: 'schedule', label: 'Schedule', icon: CalendarClock },
];
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

        <CampaignBasicDetails
            :campaign="campaign"
            :type-flow-label="campaign.type.label"
        />

        <section class="space-y-4" aria-label="Campaign sections">
            <nav class="flex flex-wrap gap-2" aria-label="Campaign tabs">
                <Button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    :variant="selectedTab === tab.id ? 'default' : 'outline'"
                    :aria-pressed="selectedTab === tab.id"
                    @click="selectedTab = tab.id"
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
                        <div class="mt-2 text-2xl font-semibold">0</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-muted-foreground">Upload</div>
                        <div class="mt-2 text-2xl font-semibold">Pending</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-muted-foreground">
                            Schedule
                        </div>
                        <div class="mt-2 text-2xl font-semibold">Not set</div>
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="selectedTab === 'contacts'">
                <CardHeader>
                    <CardTitle>Contacts</CardTitle>
                    <CardDescription>No contacts added yet.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        Contact records will appear here.
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="selectedTab === 'upload_contacts'">
                <CardHeader>
                    <CardTitle>Upload Contacts</CardTitle>
                    <CardDescription>
                        Contact upload is pending.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="grid min-h-48 place-items-center rounded-lg border border-dashed bg-muted/20 p-8 text-center"
                    >
                        <div>
                            <Upload
                                class="mx-auto size-8 text-muted-foreground"
                            />
                            <div class="mt-3 text-sm font-medium">
                                Upload area
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                No file selected
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

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
