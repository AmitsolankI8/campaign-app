<script setup lang="ts" generic="T extends { id: string }">
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Filter,
    RotateCcw,
    Search,
} from '@lucide/vue';
import { computed, ref, useId, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDataTableRequest } from '@/composables/useDataTableRequest';
import { useDateTimeFormat } from '@/composables/useDateTimeFormat';
import type {
    DataTableColumn,
    DataTableData,
    DataTableState,
    DataTableFilters,
    DataTableRequestHandler,
} from '@/types/data-table';

const props = withDefaults(
    defineProps<{
        data: DataTableData<T>;
        columns: DataTableColumn<T>[];
        url?: string;
        request?: DataTableRequestHandler;
        requestData?: DataTableFilters | (() => DataTableFilters);
        reloadProps?: string[];
        propName: string;
        caption?: string;
        showPagination?: boolean;
        showPerPage?: boolean;
        showSummary?: boolean;
        showFilter?: boolean;
        showRowNumbers?: boolean;
        perPageOptions?: number[];
        pageButtonCount?: number;
        emptyMessage?: string;
    }>(),
    {
        caption: 'Records',
        showPagination: true,
        showPerPage: true,
        showSummary: true,
        showFilter: true,
        showRowNumbers: true,
        pageButtonCount: 5,
        emptyMessage: 'No records found.',
    },
);

const id = useId();
const loading = ref(false);
const error = ref('');
const search = ref(props.data.state.search);
const searchColumn = ref(props.data.state.search_column || '__all');
const filters = ref<DataTableFilters>(
    JSON.parse(JSON.stringify(props.data.state.filters)),
);
const sendRequest = useDataTableRequest();
const { formatDate, formatTime, formatDateTime } = useDateTimeFormat();

watch(
    () => JSON.stringify(props.data.state),
    () => {
        const state = props.data.state;
        search.value = state.search;
        searchColumn.value = state.search_column || '__all';
        filters.value = JSON.parse(JSON.stringify(state.filters));
    },
);

const searchableColumns = computed(() =>
    props.columns.filter(
        (column) =>
            column.searchable !== false &&
            props.data.options.searchable_columns.includes(column.key),
    ),
);

const perPageOptions = computed(() =>
    [
        ...new Set([
            ...(
                props.perPageOptions ?? props.data.options.per_page_options
            ).filter((size) =>
                props.data.options.per_page_options.includes(size),
            ),
            props.data.meta.per_page,
        ]),
    ].sort((a, b) => a - b),
);

const pages = computed(() => {
    const count = Math.max(1, Math.min(15, Math.floor(props.pageButtonCount)));
    const { current_page: current, last_page: last } = props.data.meta;
    const start = Math.max(
        1,
        Math.min(current - Math.floor(count / 2), last - count + 1),
    );
    const end = Math.min(last, start + count - 1);
    const result: (number | string)[] = [];

    if (start > 1) {
        result.push(1);
    }

    if (start > 2) {
        result.push('before');
    }

    for (let page = start; page <= end; page++) {
        result.push(page);
    }

    if (end < last - 1) {
        result.push('after');
    }

    if (end < last) {
        result.push(last);
    }

    return result;
});

async function visit(changes: Partial<DataTableState> & { page?: number }) {
    if (loading.value) {
        return;
    }

    error.value = '';
    loading.value = true;

    try {
        await sendRequest(
            JSON.parse(
                JSON.stringify({
                    ...props.data.state,
                    page: props.data.meta.current_page,
                    ...changes,
                }),
            ),
            {
                url: props.url,
                namespace: props.data.options.query_namespace,
                propName: props.propName,
                defaults: {
                    ...props.data.options.defaults,
                    filters: props.data.options.filter_defaults,
                },
                reloadProps: props.reloadProps,
                requestData: props.requestData,
                request: props.request,
            },
        );
    } catch (cause) {
        error.value =
            cause instanceof Error
                ? cause.message
                : 'Unable to load records. Please try again.';
    } finally {
        loading.value = false;
    }
}

function applyFilter() {
    visit({
        search: search.value.trim(),
        search_column: searchColumn.value === '__all' ? '' : searchColumn.value,
        page: 1,
        filters: filters.value,
    });
}

function clearFilter() {
    search.value = '';
    searchColumn.value = '__all';
    filters.value = JSON.parse(
        JSON.stringify(props.data.options.filter_defaults),
    );
    visit({ search: '', search_column: '', filters: filters.value, page: 1 });
}

function isSortable(column: DataTableColumn<T>) {
    return (
        column.sortable !== false &&
        props.data.options.sortable_columns.includes(column.key)
    );
}

function sort(column: DataTableColumn<T>) {
    if (!isSortable(column)) {
        return;
    }

    visit({
        sort: column.key,
        direction:
            props.data.state.sort === column.key &&
            props.data.state.direction === 'asc'
                ? 'desc'
                : 'asc',
        page: 1,
    });
}

function displayValue(row: T, column: DataTableColumn<T>) {
    const value = column.value ? column.value(row) : row[column.key as keyof T];

    if (column.format && typeof value === 'string') {
        const formatters = {
            date: formatDate,
            time: formatTime,
            datetime: formatDateTime,
        };

        return formatters[column.format](value) ?? '—';
    }

    return value ?? '—';
}
</script>

<template>
    <section
        class="min-w-0 space-y-4"
        :aria-label="caption"
        :aria-busy="loading"
    >
        <form
            v-if="showFilter"
            class="flex flex-wrap items-center gap-2 rounded-xl border bg-muted/20 p-4"
            role="search"
            :aria-label="`Filter ${caption}`"
            @submit.prevent="applyFilter"
        >
            <slot
                name="filters"
                :filters="filters"
                :search="search"
                :search-column="searchColumn"
                :set-search="(value: string) => (search = value)"
                :set-search-column="
                    (value: string) => (searchColumn = value || '__all')
                "
                :apply="applyFilter"
                :clear="clearFilter"
                :loading="loading"
            >
                <div class="flex w-full min-w-0 sm:max-w-xl">
                    <Select
                        v-if="searchableColumns.length"
                        v-model="searchColumn"
                        :disabled="loading"
                    >
                        <SelectTrigger
                            class="w-36 shrink-0 rounded-r-none border-r-0 shadow-none"
                            aria-label="Search column"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">All columns</SelectItem>
                            <SelectItem
                                v-for="column in searchableColumns"
                                :key="column.key"
                                :value="column.key"
                                >{{ column.label }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            :class="[
                                'pl-9',
                                searchableColumns.length
                                    ? 'rounded-l-none'
                                    : '',
                            ]"
                            placeholder="Search records…"
                            aria-label="Search records"
                            :disabled="loading"
                            maxlength="255"
                        />
                    </div>
                </div>
            </slot>
            <slot
                name="extra-filters"
                :filters="filters"
                :loading="loading"
                :apply="applyFilter"
                :clear="clearFilter"
            />
            <Button
                type="submit"
                size="icon"
                :disabled="loading"
                aria-label="Apply filters"
                title="Apply filters"
                ><Filter class="size-4"
            /></Button>
            <Button
                type="button"
                size="icon"
                variant="outline"
                :disabled="loading"
                aria-label="Clear filters"
                title="Clear filters"
                @click="clearFilter"
                ><RotateCcw class="size-4"
            /></Button>
        </form>

        <p v-if="error" role="alert" class="text-sm text-destructive">
            {{ error }}
        </p>
        <div
            v-if="showSummary || showPerPage || $slots.toolbar"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p
                v-if="showSummary"
                class="text-sm text-muted-foreground"
                role="status"
                aria-live="polite"
            >
                <slot name="summary" :meta="data.meta" :loading="loading">
                    {{
                        loading
                            ? 'Loading…'
                            : `Showing ${data.meta.from ?? 0}–${data.meta.to ?? 0} of ${data.meta.total} records`
                    }}
                </slot>
            </p>
            <slot name="toolbar" />
            <div v-if="showPerPage" class="ml-auto flex items-center gap-2">
                <label
                    :for="`${id}-per-page`"
                    class="text-sm text-muted-foreground"
                    >Rows per page</label
                >
                <Select
                    :model-value="String(data.meta.per_page)"
                    :disabled="loading"
                    @update:model-value="
                        visit({ per_page: Number($event), page: 1 })
                    "
                >
                    <SelectTrigger :id="`${id}-per-page`" class="w-20"
                        ><SelectValue
                    /></SelectTrigger>
                    <SelectContent
                        ><SelectItem
                            v-for="size in perPageOptions"
                            :key="size"
                            :value="String(size)"
                            >{{ size }}</SelectItem
                        ></SelectContent
                    >
                </Select>
            </div>
        </div>

        <div
            class="overflow-x-auto rounded-xl border"
            :class="{ 'opacity-60': loading }"
        >
            <table class="w-full text-sm">
                <caption class="sr-only">
                    {{
                        caption
                    }}
                </caption>
                <thead class="bg-muted/50 text-left">
                    <slot
                        name="header"
                        :columns="columns"
                        :sort="sort"
                        :state="data.state"
                    >
                        <tr>
                            <th
                                v-if="showRowNumbers"
                                scope="col"
                                class="w-16 px-4 py-3 font-medium"
                            >
                                #
                            </th>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                scope="col"
                                class="px-4 py-3 font-medium"
                                :class="column.headerClass"
                                :aria-sort="
                                    isSortable(column)
                                        ? data.state.sort === column.key
                                            ? data.state.direction === 'asc'
                                                ? 'ascending'
                                                : 'descending'
                                            : 'none'
                                        : undefined
                                "
                            >
                                <button
                                    v-if="isSortable(column)"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-sm text-left focus-visible:outline-2 focus-visible:outline-ring disabled:opacity-50"
                                    :disabled="loading"
                                    :aria-label="`Sort by ${column.label} ${data.state.sort === column.key && data.state.direction === 'asc' ? 'descending' : 'ascending'}`"
                                    @click="sort(column)"
                                >
                                    <slot
                                        :name="`header-${column.key}`"
                                        :column="column"
                                        >{{ column.label }}</slot
                                    >
                                    <ArrowUp
                                        v-if="
                                            data.state.sort === column.key &&
                                            data.state.direction === 'asc'
                                        "
                                        class="size-3.5 shrink-0"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            data.state.sort === column.key
                                        "
                                        class="size-3.5 shrink-0"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="size-3.5 shrink-0 text-muted-foreground"
                                    />
                                </button>
                                <slot
                                    v-else
                                    :name="`header-${column.key}`"
                                    :column="column"
                                    >{{ column.label }}</slot
                                >
                            </th>
                        </tr>
                    </slot>
                </thead>
                <tbody>
                    <tr v-if="!data.data.length">
                        <td
                            :colspan="columns.length + Number(showRowNumbers)"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            <slot name="empty">{{ emptyMessage }}</slot>
                        </td>
                    </tr>
                    <template
                        v-for="(row, rowIndex) in data.data"
                        :key="row.id"
                    >
                        <slot
                            name="row"
                            :row="row"
                            :index="rowIndex"
                            :number="(data.meta.from ?? 1) + rowIndex"
                            :columns="columns"
                        >
                            <tr class="border-t">
                                <td
                                    v-if="showRowNumbers"
                                    class="px-4 py-3 text-muted-foreground tabular-nums"
                                >
                                    {{ (data.meta.from ?? 1) + rowIndex }}
                                </td>
                                <td
                                    v-for="column in columns"
                                    :key="column.key"
                                    class="px-4 py-3"
                                    :class="column.cellClass"
                                >
                                    <slot
                                        :name="`cell-${column.key}`"
                                        :row="row"
                                        :column="column"
                                        :value="displayValue(row, column)"
                                        :index="rowIndex"
                                        >{{ displayValue(row, column) }}</slot
                                    >
                                </td>
                            </tr>
                        </slot>
                    </template>
                </tbody>
                <slot name="footer" :data="data" />
            </table>
        </div>

        <nav
            v-if="showPagination && data.meta.last_page > 1"
            class="flex flex-wrap items-center justify-end gap-1"
            :aria-label="`${caption} pagination`"
        >
            <Button
                v-if="data.meta.current_page > 1"
                type="button"
                variant="outline"
                size="sm"
                :disabled="loading"
                @click="visit({ page: data.meta.current_page - 1 })"
                ><ChevronLeft class="size-4" />Previous</Button
            >
            <template v-for="page in pages" :key="page">
                <Button
                    v-if="typeof page === 'number'"
                    type="button"
                    :variant="
                        page === data.meta.current_page ? 'default' : 'outline'
                    "
                    size="sm"
                    class="min-w-9"
                    :disabled="loading || page === data.meta.current_page"
                    :aria-current="
                        page === data.meta.current_page ? 'page' : undefined
                    "
                    :aria-label="`Page ${page}`"
                    @click="visit({ page })"
                    >{{ page }}</Button
                >
                <span
                    v-else
                    class="px-2 text-muted-foreground"
                    aria-hidden="true"
                    >…</span
                >
            </template>
            <Button
                v-if="data.meta.current_page < data.meta.last_page"
                type="button"
                variant="outline"
                size="sm"
                :disabled="loading"
                @click="visit({ page: data.meta.current_page + 1 })"
                >Next<ChevronRight class="size-4"
            /></Button>
        </nav>
    </section>
</template>
