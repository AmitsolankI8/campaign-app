<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { edit, index } from '@/actions/App/Http/Controllers/CampaignController';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import CampaignBasicDetails from '@/pages/campaigns/components/CampaignBasicDetails.vue';
import { useCampaignTabs } from '@/pages/campaigns/once-off/useCampaignTabs';
import type { Campaign } from '@/pages/campaigns/types';

defineProps<{ campaign: Campaign }>();
const { hasPermissions } = usePermissions();
const { tabs, selectedTab } = useCampaignTabs();
</script>

<template>
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

        <div
            class="grid flex-1 grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]"
        >
            <section class="min-w-0 space-y-4" aria-label="Campaign sections">
                <nav class="flex flex-wrap gap-2" aria-label="Campaign tabs">
                    <Button
                        v-for="tab in tabs"
                        :key="tab.id"
                        as-child
                        :variant="
                            selectedTab === tab.id ? 'default' : 'outline'
                        "
                    >
                        <Link
                            :href="tab.href"
                            :aria-current="
                                selectedTab === tab.id ? 'page' : undefined
                            "
                            preserve-scroll
                        >
                            <component :is="tab.icon" class="size-4" />
                            {{ tab.label }}
                        </Link>
                    </Button>
                </nav>

                <slot />
            </section>
            <aside
                id="campaign-basic-details"
                class="order-first min-w-0 lg:order-last"
                aria-label="Basic details"
            >
                <CampaignBasicDetails
                    :campaign="campaign"
                    sidebar
                    class="h-full"
                />
            </aside>
        </div>
    </div>
</template>
