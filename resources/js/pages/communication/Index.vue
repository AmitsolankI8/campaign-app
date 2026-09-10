<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index } from '@/actions/App/Http/Controllers/Settings/CommunicationSettingsController';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import type { CommunicationProvider } from '@/types/communication';
import ProviderRow from './ProviderRow.vue';

const props = defineProps<{
    channels: {
        key: string;
        name: string;
        providers: CommunicationProvider[];
    }[];
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Communication', href: index() }] },
});
const { hasPermissions } = usePermissions();
const selectedChannel = ref(props.channels[0]?.key ?? '');
</script>

<template>
    <Head title="Communication" />
    <div
        v-if="hasPermissions(['communication.view'])"
        class="flex flex-1 flex-col gap-6 p-4"
    >
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Communication</h1>
            <p class="text-sm text-muted-foreground">
                Manage providers and credentials for each communication channel.
            </p>
        </div>
        <p v-if="channels.length === 0" class="text-sm text-muted-foreground">
            No communication providers are available.
        </p>
        <nav aria-label="Communication channels" class="flex flex-wrap gap-2">
            <Button
                v-for="channel in channels"
                :key="channel.key"
                :variant="
                    selectedChannel === channel.key ? 'default' : 'outline'
                "
                :aria-pressed="selectedChannel === channel.key"
                @click="selectedChannel = channel.key"
                >{{ channel.name }}</Button
            >
        </nav>
        <p class="text-sm text-muted-foreground">
            Lower priority numbers rank first (1 is highest). Equal priorities
            follow the displayed provider order. Settings are independent for
            each channel.
        </p>
        <section
            v-for="channel in channels"
            v-show="selectedChannel === channel.key"
            :key="channel.key"
            :aria-label="channel.name"
            class="overflow-x-auto rounded-lg border"
        >
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Provider
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Status
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Priority
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Credentials
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <ProviderRow
                        v-for="provider in channel.providers"
                        :key="provider.provider"
                        :channel="channel.key"
                        :channel-name="channel.name"
                        :provider="provider"
                    />
                </tbody>
            </table>
        </section>
    </div>
</template>
