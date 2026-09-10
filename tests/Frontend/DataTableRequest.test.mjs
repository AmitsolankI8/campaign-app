import assert from 'node:assert/strict';
import test from 'node:test';
import { dataTableUrl as buildUrl } from '../../resources/js/lib/dataTableRequest.ts';

const state = {
    page: 2,
    per_page: 10,
    search: '',
    search_column: '',
    sort: 'created_at',
    direction: 'desc',
    filters: {},
};
const query = (url) => new URL(url, 'http://localhost').searchParams;
const defaults = { ...state, page: 1 };
const dataTableUrl = (current, endpoint, namespace, state, extra = {}) =>
    buildUrl(current, endpoint, namespace, state, defaults, extra);

test('one table preserves the other table and unrelated page parameters', () => {
    const url = dataTableUrl(
        '/dashboard?tab=activity&roles[page]=3&roles[search]=Editor&users[page]=1',
        undefined,
        'users',
        state,
    );
    const params = query(url);
    assert.equal(params.get('tab'), 'activity');
    assert.equal(params.get('roles[page]'), '3');
    assert.equal(params.get('roles[search]'), 'Editor');
    assert.equal(params.get('users[page]'), '2');
});

test('clear removes only its own old filters including nested array values', () => {
    const params = query(
        dataTableUrl(
            '/dashboard?users[filters][roles][0]=old&users[search]=old&roles[filters][assigned]=1',
            undefined,
            'users',
            state,
        ),
    );
    assert.equal(params.has('users[filters][roles][0]'), false);
    assert.equal(params.has('users[search]'), false);
    assert.equal(params.get('roles[filters][assigned]'), '1');
});

test('custom filter values are encoded without corrupting query parameters', () => {
    const params = query(
        dataTableUrl('/dashboard', undefined, 'users', {
            ...state,
            filters: {
                roles: ['a&b', 'editor'],
                active: false,
                count: 0,
                range: { start: '2026-01-01' },
            },
        }),
    );
    assert.equal(params.get('users[filters][roles][0]'), 'a&b');
    assert.equal(params.get('users[filters][roles][1]'), 'editor');
    assert.equal(params.get('users[filters][active]'), '0');
    assert.equal(params.get('users[filters][count]'), '0');
    assert.equal(params.get('users[filters][range][start]'), '2026-01-01');
});

test('explicit URL and extra context can override unrelated page values', () => {
    const url = dataTableUrl(
        '/dashboard?tab=old&project=1&roles[page]=4',
        '/dashboard?tab=new',
        'users',
        state,
        { project: 2 },
    );
    const params = query(url);
    assert.equal(params.get('tab'), 'new');
    assert.equal(params.get('project'), '2');
    assert.equal(params.get('roles[page]'), '4');
});

test('legacy flat requests preserve unrelated state and remove stale filters', () => {
    const params = query(
        dataTableUrl(
            '/users?tab=activity&page=7&filters[role]=old',
            undefined,
            '',
            state,
        ),
    );
    assert.equal(params.get('tab'), 'activity');
    assert.equal(params.get('page'), '2');
    assert.equal(params.has('filters[role]'), false);
});

test('sequential table updates preserve the preceding table update', () => {
    const first = dataTableUrl(
        '/dashboard?tab=activity',
        undefined,
        'users',
        state,
    );
    const second = dataTableUrl(first, undefined, 'roles', {
        ...state,
        page: 4,
    });
    assert.equal(query(second).get('users[page]'), '2');
    assert.equal(query(second).get('roles[page]'), '4');
});

test('default state removes all table parameters from the URL', () => {
    assert.equal(
        dataTableUrl(
            '/users?page=2&sort=email&search=old&filters[role]=editor',
            undefined,
            '',
            defaults,
        ),
        '/users',
    );
});

test('sorting includes only non-default sorting parameters', () => {
    assert.equal(
        dataTableUrl('/users', undefined, '', {
            ...defaults,
            sort: 'email',
            direction: 'asc',
        }),
        '/users?sort=email&direction=asc',
    );
    assert.equal(
        dataTableUrl('/users', undefined, '', { ...defaults, sort: 'email' }),
        '/users?sort=email',
    );
});

test('pagination keeps active filters and custom page size but omits defaults', () => {
    const params = query(
        buildUrl(
            '/users',
            undefined,
            '',
            {
                ...state,
                per_page: 25,
                filters: { role: 'editor', assigned: null },
            },
            { ...defaults, filters: { role: null, assigned: null } },
        ),
    );
    assert.deepEqual(
        [...params.entries()],
        [
            ['page', '2'],
            ['per_page', '25'],
            ['filters[role]', 'editor'],
        ],
    );
});

test('server defaults for each table are respected including filter value types', () => {
    const roleDefaults = {
        ...defaults,
        per_page: 30,
        sort: 'display_name',
        direction: 'asc',
        filters: {
            assigned: false,
            names: ['editor'],
            range: { start: '2026-01-01', end: '2026-02-01' },
        },
    };
    assert.equal(
        buildUrl(
            '/overview?users[page]=4&roles[page]=2',
            undefined,
            'roles',
            {
                ...roleDefaults,
                filters: {
                    assigned: '0',
                    names: ['editor'],
                    range: { end: '2026-02-01', start: '2026-01-01' },
                },
            },
            roleDefaults,
        ),
        '/overview?users%5Bpage%5D=4',
    );
});

test('clearing a non-empty default filter sends the explicit cleared value', () => {
    const params = query(
        buildUrl(
            '/users?filters[role]=admin',
            undefined,
            '',
            { ...defaults, filters: { role: null } },
            { ...defaults, filters: { role: 'editor' } },
        ),
    );
    assert.equal(params.get('filters[role]'), '');
});
