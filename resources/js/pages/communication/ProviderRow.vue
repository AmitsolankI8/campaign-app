<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/Settings/CommunicationSettingsController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import type { CommunicationProvider } from '@/types/communication';

const props = defineProps<{
    channel: string;
    channelName: string;
    provider: CommunicationProvider;
}>();
const { hasPermissions } = usePermissions();
const canEdit = computed(() =>
    hasPermissions(['communication.view', 'communication.edit'], true),
);
const modalOpen = ref(false);
const savedData = () => ({
    is_active: props.provider.is_active,
    priority: props.provider.priority,
    credentials: { ...props.provider.credentials },
});
const rowForm = useForm(savedData());
const credentialForm = useForm(savedData());
const busy = computed(() => rowForm.processing || credentialForm.processing);
const configured = computed(() =>
    props.provider.fields
        .filter((field) => field.required)
        .every((field) =>
            field.secret
                ? props.provider.saved_secrets.includes(field.key)
                : Boolean(props.provider.credentials[field.key]),
        ),
);
const rowError = computed(() => Object.values(rowForm.errors)[0]);
const fieldId = (key: string) =>
    `${props.channel}-${props.provider.provider}-${key}`;
const errorFor = (key: string) =>
    (credentialForm.errors as Record<string, string>)[key];
const endpoint = () =>
    update.url({ channel: props.channel, provider: props.provider.provider });

watch(
    () => props.provider,
    () => {
        if (!rowForm.isDirty) {
            rowForm.defaults(savedData());
            rowForm.reset();
        }
    },
);

function setModalOpen(open: boolean) {
    if (busy.value) {
        return;
    }

    credentialForm.defaults(savedData());
    credentialForm.resetAndClearErrors();
    modalOpen.value = open;
}

function saveRow() {
    if (!canEdit.value || busy.value) {
        return;
    }

    rowForm.credentials = { ...props.provider.credentials };
    rowForm.put(endpoint(), {
        preserveScroll: true,
        onSuccess: async () => {
            await nextTick();
            rowForm.defaults(savedData());
            rowForm.reset();
        },
        onError: () => {
            rowForm.is_active = props.provider.is_active;
        },
    });
}

function toggleActive(value: boolean | 'indeterminate') {
    if (!canEdit.value || busy.value) {
        return;
    }

    rowForm.is_active = value === true;
    saveRow();
}

function saveCredentials() {
    if (!canEdit.value || busy.value) {
        return;
    }

    credentialForm.is_active = props.provider.is_active;
    credentialForm.priority = props.provider.priority;
    credentialForm.put(endpoint(), {
        preserveScroll: true,
        onSuccess: async () => {
            await nextTick();
            credentialForm.defaults(savedData());
            credentialForm.resetAndClearErrors();
            rowForm.clearErrors();
            modalOpen.value = false;
        },
    });
}
</script>

<template>
    <tr class="align-top">
        <th scope="row" class="px-4 py-4 font-medium">{{ provider.name }}</th>
        <td class="px-4 py-4">
            <div v-if="canEdit" class="flex min-h-9 items-center gap-2">
                <Checkbox
                    :id="fieldId('active')"
                    :model-value="rowForm.is_active"
                    :disabled="busy"
                    @update:model-value="toggleActive"
                />
                <Label :for="fieldId('active')"
                    >{{ rowForm.is_active ? 'Active' : 'Inactive'
                    }}<span class="sr-only">
                        {{ provider.name }} {{ channelName }}</span
                    ></Label
                >
            </div>
            <Badge
                v-else
                :variant="provider.is_active ? 'default' : 'secondary'"
                >{{ provider.is_active ? 'Active' : 'Inactive' }}</Badge
            >
            <p
                v-if="rowForm.processing"
                role="status"
                class="mt-2 text-xs text-muted-foreground"
            >
                Saving...
            </p>
            <InputError :message="rowError" class="mt-2 max-w-xs" />
        </td>
        <td class="px-4 py-4">
            <form
                v-if="canEdit"
                class="flex items-center gap-2"
                @submit.prevent="saveRow"
            >
                <Label :for="fieldId('priority')" class="sr-only"
                    >{{ provider.name }} {{ channelName }} priority</Label
                >
                <Input
                    :id="fieldId('priority')"
                    v-model="rowForm.priority"
                    type="number"
                    min="1"
                    max="999"
                    required
                    class="w-20"
                    :disabled="busy"
                />
                <Button
                    v-if="Number(rowForm.priority) !== provider.priority"
                    type="submit"
                    size="sm"
                    variant="outline"
                    :disabled="busy"
                    >Save</Button
                >
            </form>
            <span v-else>{{ provider.priority }}</span>
        </td>
        <td class="px-4 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-muted-foreground">{{
                    configured ? 'Configured' : 'Not configured'
                }}</span>
                <Dialog :open="modalOpen" @update:open="setModalOpen">
                    <DialogTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="busy"
                            :aria-label="`${canEdit ? 'Edit' : 'View'} ${provider.name} ${channelName} credentials`"
                            >{{
                                canEdit ? 'Credentials' : 'View credentials'
                            }}</Button
                        >
                    </DialogTrigger>
                    <DialogContent
                        class="max-h-[85vh] overflow-y-auto sm:max-w-lg"
                        @interact-outside="busy && $event.preventDefault()"
                        @escape-key-down="busy && $event.preventDefault()"
                    >
                        <DialogHeader>
                            <DialogTitle
                                >{{ provider.name }} credentials</DialogTitle
                            >
                            <DialogDescription
                                >{{ channelName }} settings. Saved secrets stay
                                hidden. Leave a secret blank to keep its current
                                value.</DialogDescription
                            >
                        </DialogHeader>
                        <form
                            v-if="canEdit"
                            class="space-y-5"
                            @submit.prevent="saveCredentials"
                        >
                            <fieldset :disabled="busy" class="space-y-4">
                                <p class="text-sm text-muted-foreground">
                                    Fields marked * are required before
                                    activating this provider.
                                </p>
                                <div
                                    v-for="field in provider.fields"
                                    :key="field.key"
                                    class="grid gap-2"
                                >
                                    <Label :for="fieldId(field.key)"
                                        >{{ field.label
                                        }}{{
                                            field.required ? ' *' : ''
                                        }}</Label
                                    >
                                    <textarea
                                        v-if="field.type === 'textarea'"
                                        :id="fieldId(field.key)"
                                        v-model="
                                            credentialForm.credentials[
                                                field.key
                                            ]
                                        "
                                        rows="4"
                                        autocomplete="off"
                                        spellcheck="false"
                                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring"
                                        :placeholder="
                                            provider.saved_secrets.includes(
                                                field.key,
                                            )
                                                ? 'Saved - leave blank to keep'
                                                : 'Paste private key'
                                        "
                                    />
                                    <select
                                        v-else-if="field.type === 'select'"
                                        :id="fieldId(field.key)"
                                        v-model="
                                            credentialForm.credentials[
                                                field.key
                                            ]
                                        "
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs"
                                    >
                                        <option value="">
                                            Select
                                            {{ field.label.toLowerCase() }}
                                        </option>
                                        <option
                                            v-for="option in field.options ??
                                            []"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <Input
                                        v-else
                                        :id="fieldId(field.key)"
                                        v-model="
                                            credentialForm.credentials[
                                                field.key
                                            ]
                                        "
                                        :type="field.type"
                                        :autocomplete="
                                            field.secret
                                                ? 'new-password'
                                                : 'off'
                                        "
                                        :placeholder="
                                            provider.saved_secrets.includes(
                                                field.key,
                                            )
                                                ? 'Saved - leave blank to keep'
                                                : undefined
                                        "
                                    />
                                    <InputError
                                        :message="
                                            errorFor(`credentials.${field.key}`)
                                        "
                                    />
                                </div>
                                <InputError
                                    :message="credentialForm.errors.credentials"
                                />
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :disabled="busy"
                                        @click="setModalOpen(false)"
                                        >Cancel</Button
                                    >
                                    <Button type="submit" :disabled="busy">{{
                                        credentialForm.processing
                                            ? 'Saving...'
                                            : 'Save credentials'
                                    }}</Button>
                                </DialogFooter>
                            </fieldset>
                        </form>
                        <dl
                            v-else-if="hasPermissions(['communication.view'])"
                            class="grid gap-4 text-sm"
                        >
                            <div
                                v-for="field in provider.fields"
                                :key="field.key"
                            >
                                <dt class="text-muted-foreground">
                                    {{ field.label }}
                                </dt>
                                <dd class="break-all">
                                    {{
                                        field.secret
                                            ? provider.saved_secrets.includes(
                                                  field.key,
                                              )
                                                ? 'Configured'
                                                : 'Not configured'
                                            : provider.credentials[field.key] ||
                                              'Not configured'
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </DialogContent>
                </Dialog>
            </div>
        </td>
    </tr>
</template>
