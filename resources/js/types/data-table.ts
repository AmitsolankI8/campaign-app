import type { HTMLAttributes } from 'vue';

export type DataTableFilterValue =
    | string
    | number
    | boolean
    | null
    | DataTableFilterValue[]
    | { [key: string]: DataTableFilterValue };
export type DataTableFilters = Record<string, DataTableFilterValue>;
export type DataTableRequestContext = {
    url: string;
    state: DataTableState & { page: number };
    only: string[];
};
export type DataTableRequestHandler = (
    context: DataTableRequestContext,
) => Promise<void>;

export type DataTableState = {
    search: string;
    search_column: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
    filters: DataTableFilters;
};

export type DataTableData<T> = {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
    state: DataTableState;
    options: {
        defaults: Omit<DataTableState, 'filters'> & { page: number };
        query_namespace: string;
        filter_defaults: DataTableFilters;
        per_page_options: number[];
        searchable_columns: string[];
        sortable_columns: string[];
    };
};

export type DataTableColumn<T> = {
    key: Extract<keyof T, string> | (string & {});
    label: string;
    sortable?: boolean;
    searchable?: boolean;
    format?: 'date' | 'time' | 'datetime';
    headerClass?: HTMLAttributes['class'];
    cellClass?: HTMLAttributes['class'];
    value?: (row: T) => unknown;
};
