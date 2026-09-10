<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    index,
    store,
    update,
} from '@/actions/App/Http/Controllers/CampaignController';
import InputError from '@/components/InputError.vue';
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
import { dashboard } from '@/routes';
import type { Campaign, CampaignTypeOption } from './types';

const props = defineProps<{
    campaign?: Campaign;
    campaignTypes?: CampaignTypeOption[];
}>();

const { hasPermissions } = usePermissions();
const isEditing = Boolean(props.campaign);

const form = useForm({
    name: props.campaign?.name ?? '',
    campaign_type: String(props.campaign?.type.value ?? ''),
    short_note: props.campaign?.short_note ?? '',
});

const submit = () => {
    if (!hasPermissions([isEditing ? 'campaigns.edit' : 'campaigns.create'])) {
        return;
    }

    if (props.campaign) {
        form.transform((data) => ({
            name: data.name,
            short_note: data.short_note,
        })).put(update.url(props.campaign.id), {
            preserveScroll: true,
        });

        return;
    }

    form.transform((data) => ({
        ...data,
        campaign_type: Number(data.campaign_type),
    })).post(store.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <form novalidate class="space-y-6" @submit.prevent="submit">
        <Card>
            <CardHeader>
                <CardTitle>{{
                    isEditing ? 'Campaign details' : 'New campaign'
                }}</CardTitle>
                <CardDescription>
                    {{
                        isEditing
                            ? 'Only the name and short note are editable after creation.'
                            : 'Choose the campaign type for the correct flow.'
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name" required>Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        aria-required="true"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div v-if="!isEditing" class="grid gap-2">
                    <Label for="campaign_type" required>Campaign type</Label>
                    <Select v-model="form.campaign_type">
                        <SelectTrigger
                            id="campaign_type"
                            class="w-full"
                            aria-required="true"
                        >
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in campaignTypes"
                                :key="type.value"
                                :value="String(type.value)"
                            >
                                {{ type.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.campaign_type" />
                </div>

                <div v-else class="grid gap-2">
                    <Label>Campaign type</Label>
                    <div
                        class="flex h-9 items-center rounded-md border bg-muted/30 px-3 text-sm text-muted-foreground"
                    >
                        {{ campaign?.type.label }}
                    </div>
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label for="short_note">Short note</Label>
                    <textarea
                        id="short_note"
                        v-model="form.short_note"
                        maxlength="255"
                        rows="4"
                        class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                    />
                    <InputError :message="form.errors.short_note" />
                </div>
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Button type="button" variant="outline" as-child>
                <Link
                    :href="
                        campaign && hasPermissions(['campaigns.view'])
                            ? campaign.show_url
                            : hasPermissions(['campaigns.view'])
                              ? index.url()
                              : dashboard.url()
                    "
                    >Cancel</Link
                >
            </Button>
            <Button
                v-if="
                    hasPermissions([
                        isEditing ? 'campaigns.edit' : 'campaigns.create',
                    ])
                "
                type="submit"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                {{ isEditing ? 'Update campaign' : 'Create campaign' }}
            </Button>
        </div>
    </form>
</template>
