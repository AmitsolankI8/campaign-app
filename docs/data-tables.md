# Server-driven data tables

Users and roles use `resources/js/components/data-table/DataTable.vue` with the existing Button, Input, and Select components. No table dependency is required. Users have a dedicated role dropdown; text search covers names and email, excluding roles. Apply combines the selected role with search, and Clear restores All roles.

## Add a table to another module

1. Extend `App\Http\Requests\DataTableRequest`. Implement `authorize()`, `searchableColumns()`, and `sortableColumns()`. Keys are public column names; values are trusted database columns or search closures. See `IndexUserRequest` and `IndexRoleRequest` for full-name, relationship, and count searches.
2. Pass your scoped Eloquent query, validated request, and row resource to `App\Support\DataTable::make()`. Select needed attributes and eager-load relationships used by the resource. Apply `select()` before `withCount()`.
3. Pass the returned prop and column definitions to the Vue component. It uses the current page URL by default; an explicit Wayfinder URL is optional.

```php
public function index(IndexUserRequest $request): Response
{
    return Inertia::render('user-management/users/Index', [
        'users' => fn () => DataTable::make(
            User::query()
                ->select(['id', 'public_id', 'first_name', 'last_name', 'email', 'created_at'])
                ->with('roles:id,name,display_name'),
            $request,
            UserRowResource::class,
        ),
    ]);
}
```

```vue
<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import type { DataTableColumn, DataTableData } from '@/types/data-table';

type UserRow = { id: string; full_name: string; created_at: string | null };
defineProps<{ users: DataTableData<UserRow> }>();

const columns: DataTableColumn<UserRow>[] = [
    { key: 'full_name', label: 'Name', cellClass: 'font-medium' },
    { key: 'created_at', label: 'Created', format: 'datetime' },
];
</script>

<template>
    <DataTable
        :data="users"
        :columns="columns"
        prop-name="users"
        caption="Users"
    >
        <template #cell-full_name="{ row }">
            <strong>{{ row.full_name }}</strong>
        </template>
    </DataTable>
</template>
```

## Options

| Vue prop          | Default             | Behavior                                                                                          |
| ----------------- | ------------------- | ------------------------------------------------------------------------------------------------- |
| `showPagination`  | `true`              | Bottom-right page buttons; hidden for zero or one page. Previous/Next appear only when available. |
| `showPerPage`     | `true`              | Top-right rows-per-page selector.                                                                 |
| `showSummary`     | `true`              | Top-left range and filtered total. Empty results show 0–0 of 0.                                   |
| `showFilter`      | `true`              | Search input, column selector, Apply and Clear buttons.                                           |
| `showRowNumbers`  | `true`              | Continuous numbering from the current page's first record.                                        |
| `perPageOptions`  | Server options      | Optional subset of server-approved sizes. The current size remains visible.                       |
| `pageButtonCount` | `5`                 | Sliding page window, plus first/last pages and ellipses. Clamped to 1–15.                         |
| `emptyMessage`    | `No records found.` | Empty table text.                                                                                 |
| `caption`         | `Records`           | Accessible table and pagination label.                                                            |

Set supported page sizes and the initial selection **in the request** so the very first response uses the correct limit, without a second browser request:

```php
public function perPageOptions(): array
{
    return [15, 30, 60];
}

public function defaultPerPage(): int
{
    return 15;
}
```

The default must be included in the allowed list. Override `defaultSort()` and `defaultDirection()` to choose the initial order; the sort key must exist in `sortableColumns()`.

Hiding pagination or the size selector only hides that control; the server always paginates. This avoids accidentally downloading the whole dataset.

## Columns and slots

Column options include `key`, `label`, `headerClass`, `cellClass`, a `value(row)` accessor, and `format: 'date' | 'time' | 'datetime'`. Dates use `useDateTimeFormat()` and authenticated preferences. Text is escaped by Vue.

Sorting and search choices follow the server's allowed keys. `sortable: false` hides a column's sort control. `searchable: false` hides its column-selection option. To exclude a field from **all-column search**, remove it from the request's `searchableColumns()` map. This server map is the authoritative filter configuration; frontend visibility cannot expand it.

- `header-{key}` customizes a column label, retaining its sorting button.
- `cell-{key}` receives `{ row, column, value, index }` and customizes cell content.
- `header` receives `{ columns, sort, state }` and replaces the header rows.
- `row` receives `{ row, index, number, columns }` and replaces the complete `<tr>`, including numbering.
- `summary`, `toolbar`, `empty`, and `footer` customize the remaining regions. `footer` should render a `<tfoot>`.
- `filters` replaces the search controls and receives their current values, setters, custom `filters` draft, and Apply/Clear functions.
- `extra-filters` adds controls alongside the default search. It receives `{ filters, loading, apply, clear }`. Bind inputs to the mutable `filters` draft; changes are applied on Apply/Enter, not while typing.

Use computed column definitions to omit unauthorized action columns. Check `usePermissions().hasPermissions([...])` in both action visibility and submission handlers, together with record-specific restrictions. See the users and roles pages for examples.

## Request and resource contract

Table URLs include only values that differ from the server defaults in `options.defaults` and `options.filter_defaults`. For example, sorting users by email ascending produces `?sort=email&direction=asc`; default page size, page 1, and empty search are omitted. Active filters and other non-default state remain on subsequent requests. Returning to a default removes its old parameter. This keeps refresh/bookmarks reliable and preserves unrelated page parameters and other tables. Custom request callbacks still receive the complete `state` alongside the compact `url`.

Requests use `page`, `per_page`, `search`, `search_column`, `sort`, and `direction`. An empty `search_column` searches all server-approved search fields. Apply (or Enter) submits the draft search; Clear resets search and column while keeping the page size and sort. Sorting and page-size changes reset to page 1 and keep the applied search. Paging keeps all applied criteria. The URL stores the applied state for refresh, bookmarks, and browser history.

The response contains `data`, `meta`, `state`, and `options`. The component renders that page directly; it never filters or sorts records locally. Inertia partial visits refresh the table prop and `auth` so authorization and preferences remain current. Other page props stay intact. `reloadProps` can add dependent summaries to the same update. Query parameters unrelated to the table are preserved.

## Add custom filters

Define validation, reset defaults, and SQL behavior on the module's table request. Each custom filter must have validation rules. Use explicit allowed keys for nested objects, and validate array items with rules such as `names.*` inside `filterRules()` (keys here are relative to the custom filters object).

```php
// In an IndexRoleRequest subclass:
public function filterRules(): array
{
    return ['assigned' => ['nullable', 'boolean']];
}

public function filterDefaults(): array
{
    return ['assigned' => null];
}

public function applyFilters(Builder $query, array $filters): void
{
    if ($filters['assigned'] !== null) {
        $query->has('users', (bool) $filters['assigned'] ? '>' : '=', 0);
    }
}
```

```vue
<DataTable :data="roles" :columns="columns" prop-name="roles">
    <template #extra-filters="{ filters, loading }">
        <select v-model="filters.assigned" :disabled="loading" aria-label="Assignment">
            <option :value="null">All roles</option>
            <option value="1">Assigned roles</option>
            <option value="0">Unassigned roles</option>
        </select>
    </template>
</DataTable>
```

Apply submits custom filters and search together and resets pagination. Sorting and pagination use the last applied filters. Clear resets custom filters to `filterDefaults()` along with search and column selection, keeping the sort and page size. Filters are copied so draft edits never mutate server props. Array and nested-object filter values are supported; empty arrays are omitted from the URL and reset to the server default. Use `nullable` for clearable values. For dates, convert user-entered local dates/times with `useDateTimeFormat().toUtcIso()` before submission and validate UTC ISO values on the server.

## Multiple tables and other page data

Give **every table on the page a unique namespace**. `forTable()` makes an independently authorized and validated Form Request from the page request, without modifying it. Your page may use its own Form Request for unrelated inputs.

```php
public function index(Request $request): Response
{
    // Authorize the page here as appropriate for the module.
    return Inertia::render('reports/Overview', [
        'summary' => fn () => ['total_users' => User::count()],
        'users' => fn () => DataTable::make(
            User::query()->with('roles'),
            IndexUserRequest::forTable($request, 'users'),
            UserRowResource::class,
        ),
        'roles' => fn () => DataTable::make(
            Role::query()->withCount('users'),
            IndexRoleRequest::forTable($request, 'roles'),
            RoleRowResource::class,
        ),
    ]);
}
```

```vue
<SummaryCards :summary="summary" />
<DataTable :data="users" :columns="userColumns" prop-name="users" />
<DataTable :data="roles" :columns="roleColumns" prop-name="roles" />
```

The namespace is sent in `options.query_namespace`, so the component gets it automatically. Requests use `users[page]`, `users[filters][assigned]`, `roles[page]`, etc. Validation errors also carry that namespace. One table's search, sorting, page size, and Clear do not alter the other table's state or page-level parameters such as `tab` or `project`. Namespace names must be simple identifiers (letters, digits, underscores, starting with a letter) and must not overlap page-level parameter names.

Keep table and expensive page props in closures so partial visits evaluate only requested props. The default transport queues table visits and builds each query from the latest page URL to avoid simultaneous table interactions cancelling or overwriting each other. Queued visits are discarded if the user navigates to another page. Existing flat single-table requests remain supported.

## Customize requests

| Prop          | Purpose                                                                                                              |
| ------------- | -------------------------------------------------------------------------------------------------------------------- |
| `url`         | Optional endpoint; defaults to the current Inertia page. Use Wayfinder for explicit backend routes.                  |
| `requestData` | Extra page-level query data, or a function returning the latest values at dispatch.                                  |
| `reloadProps` | Additional Inertia props to refresh, such as totals affected by filtering. The table and `auth` are always included. |
| `request`     | Optional async callback replacing the default transport. Receives `{ url, state, only }`.                            |

```vue
<DataTable
    :data="users"
    :columns="userColumns"
    prop-name="users"
    :request-data="() => ({ project: selectedProject })"
    :reload-props="['summary']"
/>
```

For full request control, provide a `DataTableRequestHandler`:

```ts
import { router } from '@inertiajs/vue3';
import type { DataTableRequestHandler } from '@/types/data-table';

const loadUsers: DataTableRequestHandler = ({ url, only }) =>
    new Promise((resolve, reject) => {
        router.get(
            url,
            {},
            {
                only,
                preserveState: true,
                preserveScroll: true,
                headers: { 'X-Report-Mode': 'overview' },
                onError: (errors) =>
                    reject(new Error(Object.values(errors).join(' '))),
                onFinish: () => resolve(),
            },
        );
    });
```

Pass `:request="loadUsers"`. `url` already contains merged and namespaced query parameters; `state` is the complete applied table state including `page` and `filters`; `only` includes configured reload props. The component waits for the promise and displays rejected errors. Custom handlers own transport, concurrency, and response updates. For JSON loading, update the parent-owned `data` prop with the response using the same resource envelope before resolving; for Inertia, the response updates the page prop. The default Inertia transport expects an endpoint rendering the same page component. Use a custom handler for independent JSON endpoints.

Always use a row resource to map `public_id` to `id` and return UTC ISO timestamps. Numeric model IDs, passwords, and unrelated fields stay out of table rows. `id` is also the Vue row key. Search uses bound SQL LIKE values (including SQL `%` and `_` wildcard behavior); full-name and numeric count handlers are defined by each request. Date columns are sortable but excluded from text search by default because their displayed timezone and format differ from stored UTC values.

The query helper groups search conditions inside existing query constraints, adds a unique sort tie-breaker, and recovers when a requested page exceeds the last available page.

Framework references: [Laravel pagination](https://github.com/laravel/docs/blob/13.x/pagination.md) and [Inertia manual visits](https://inertiajs.com/docs/v3/the-basics/manual-visits).
