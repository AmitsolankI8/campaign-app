import type {
    DataTableFilters,
    DataTableFilterValue,
    DataTableState,
} from '../types/data-table';

const tableKeys = [
    'page',
    'per_page',
    'search',
    'search_column',
    'sort',
    'direction',
    'filters',
];

function append(
    params: URLSearchParams,
    key: string,
    value: DataTableFilterValue,
) {
    if (value !== null && typeof value === 'object') {
        Object.entries(value).forEach(([child, item]) =>
            append(params, `${key}[${child}]`, item),
        );
    } else {
        params.append(
            key,
            value === null
                ? ''
                : typeof value === 'boolean'
                  ? value
                      ? '1'
                      : '0'
                  : String(value),
        );
    }
}

function remove(params: URLSearchParams, key: string) {
    [...params.keys()].forEach((name) => {
        if (name === key || name.startsWith(`${key}[`)) {
            params.delete(name);
        }
    });
}

export function dataTableUrl(
    currentUrl: string,
    endpoint: string | undefined,
    namespace: string,
    state: DataTableState & { page: number },
    defaults: DataTableState & { page: number },
    extra: DataTableFilters = {},
): string {
    const current = new URL(currentUrl, 'http://datatable.local');
    const target = new URL(endpoint ?? currentUrl, current);
    const params = new URLSearchParams(current.search);

    // Explicit endpoint parameters override page parameters with the same key.
    const endpointParams = new URLSearchParams(target.search);
    new Set(endpointParams.keys()).forEach((key) => params.delete(key));
    endpointParams.forEach((value, key) => params.append(key, value));
    Object.entries(extra).forEach(([key, value]) => {
        remove(params, key);
        append(params, key, value);
    });

    if (namespace) {
        remove(params, namespace);
    } else {
        tableKeys.forEach((key) => remove(params, key));
    }

    Object.entries(state).forEach(([key, value]) => {
        const parameter = namespace ? `${namespace}[${key}]` : key;

        if (key === 'filters') {
            Object.entries(state.filters).forEach(([filter, value]) => {
                if (
                    !(filter in defaults.filters) ||
                    !matchesDefault(value, defaults.filters[filter])
                ) {
                    append(params, `${parameter}[${filter}]`, value);
                }
            });
        } else if (
            !matchesDefault(value, defaults[key as keyof typeof defaults])
        ) {
            append(params, parameter, value);
        }
    });
    target.search = params.toString();

    return `${target.pathname}${target.search}${target.hash}`;
}

function matchesDefault(
    value: DataTableFilterValue,
    defaultValue: DataTableFilterValue,
): boolean {
    const actual = new URLSearchParams();
    const expected = new URLSearchParams();
    append(actual, 'value', value);
    append(expected, 'value', defaultValue);
    actual.sort();
    expected.sort();

    return actual.toString() === expected.toString();
}
