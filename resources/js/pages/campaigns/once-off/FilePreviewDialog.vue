<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ContactFilePreview } from '../types';

const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{
    preview: ContactFilePreview | null;
}>();
const page = ref(1);
const filtered = computed(() =>
    (props.preview?.rows ?? []).filter(
        (row) => Object.keys(row.errors).length > 0,
    ),
);
const pages = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / 25)),
);
const visible = computed(() =>
    filtered.value.slice((page.value - 1) * 25, page.value * 25),
);
watch(
    () => props.preview,
    () => {
        page.value = 1;
    },
);

function downloadErrors() {
    if (!props.preview) {
        return;
    }

    // Quote cells and neutralize formulas when exporting user-provided text.
    const quote = (value: string) =>
        '"' +
        (/^[=+@\-\t\r]/.test(value) ? "'" + value : value).replaceAll(
            '"',
            '""',
        ) +
        '"';
    const records = [
        ['Row', ...props.preview.headers, 'Errors'],
        ...props.preview.errors.map((message) => [
            '1',
            ...props.preview!.headers,
            message,
        ]),
        ...props.preview.rows
            .filter((row) => Object.keys(row.errors).length > 0)
            .map((row) => [
                String(row.row_number),
                ...props.preview!.headers.map(
                    (_, index) => row.values[index] ?? '',
                ),
                Object.entries(row.errors)
                    .map(
                        ([column, messages]) =>
                            `${props.preview!.headers[Number(column)]}: ${messages.join(' ')}`,
                    )
                    .join(' | '),
            ]),
    ];
    const url = URL.createObjectURL(
        new Blob(
            [
                '\uFEFF' +
                    records.map((row) => row.map(quote).join(',')).join('\r\n'),
            ],
            { type: 'text/csv;charset=utf-8' },
        ),
    );
    const link = document.createElement('a');
    link.href = url;
    link.download = 'contact-upload-errors.csv';
    link.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-6xl">
            <DialogHeader>
                <DialogTitle>Correct your contact file</DialogTitle>
                <DialogDescription>
                    {{ preview?.error_count }} errors found.
                    <template v-if="filtered.length"
                        >Only the {{ filtered.length }} rows with errors are
                        shown below.</template
                    >
                    Correct the original file and upload it again. No upload or
                    contact records have been saved.
                </DialogDescription>
            </DialogHeader>
            <div v-if="preview" class="space-y-4">
                <ul
                    v-if="preview.errors.length"
                    class="list-inside list-disc rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
                >
                    <li v-for="message in preview.errors" :key="message">
                        {{ message }}
                    </li>
                </ul>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <Button
                        v-if="preview.error_count"
                        variant="outline"
                        size="sm"
                        @click="downloadErrors"
                        >Download error report</Button
                    >
                </div>
                <div
                    v-if="filtered.length"
                    class="max-h-[50vh] overflow-auto rounded-md border"
                >
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 bg-muted">
                            <tr>
                                <th class="p-3">Row</th>
                                <th
                                    v-for="(header, index) in preview.headers"
                                    :key="index"
                                    class="min-w-40 p-3"
                                >
                                    {{ header || `Column ${index + 1}` }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in visible"
                                :key="row.row_number"
                                class="border-t"
                            >
                                <td class="p-3 align-top text-muted-foreground">
                                    {{ row.row_number }}
                                </td>
                                <td
                                    v-for="(_, index) in preview.headers"
                                    :key="index"
                                    class="max-w-80 p-3 align-top break-words"
                                    :class="
                                        row.errors[index]
                                            ? 'bg-destructive/5 ring-1 ring-destructive/30 ring-inset'
                                            : ''
                                    "
                                >
                                    <div class="whitespace-pre-wrap">
                                        {{ row.values[index] || '—' }}
                                    </div>
                                    <p
                                        v-for="message in row.errors[index] ??
                                        []"
                                        :key="message"
                                        class="mt-1 text-xs text-destructive"
                                    >
                                        {{ message }}
                                    </p>
                                </td>
                            </tr>
                            <tr v-if="visible.length === 0">
                                <td
                                    :colspan="preview.headers.length + 1"
                                    class="p-6 text-center text-muted-foreground"
                                >
                                    No matching rows.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="pages > 1"
                    class="flex items-center justify-end gap-3 text-sm"
                >
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="page === 1"
                        @click="page--"
                        >Previous</Button
                    >
                    <span>Page {{ page }} of {{ pages }}</span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="page >= pages"
                        @click="page++"
                        >Next</Button
                    >
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="open = false">Close</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
