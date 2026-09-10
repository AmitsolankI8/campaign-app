# Permission changes

- Whenever a permission is added or changed, implement the matching frontend conditions in the same change. Check all related buttons, links, navigation items, menus, forms, and submission handlers.
- Use `usePermissions().hasPermissions(['resource.action'])` with the exact permission name enforced by the backend. Shared `auth.permissions` is evaluated by Laravel authorization from `PermissionRegistry`; do not infer permissions from role names. Missing permissions must deny access by default.
- Hide unauthorized actions and navigation, including empty action columns and menu groups. Preserve record-specific restrictions such as preventing self-deletion or deleting assigned roles.
- Keep backend authorization in place: frontend visibility is not a security boundary. Verify both allowed and denied users, including permissions inherited through roles, when changing permissions.

- `hasPermissions(permissionNames, checkAll = false)` accepts an array: by default any allowed permission grants access (return immediately on the first match); pass `true` to require every listed permission. An empty array denies access. Use this helper for all frontend permission checks.

# User preferences

- Users must always have a matching `user_preferences` row. Keep `UserFactory` preference-complete so `User::factory()->create()` works without extra preference setup in tests or seeders.
- Share authenticated user preference references through cached `auth.preferences` from `App\Support\UserPreferences`. Include the selected country, timezone, language, and number/date/time format records so frontend code can derive behavior without extra requests.
- Cache per-user preferences for one week and invalidate the cache when `UserPreference` is saved or deleted.
- Keep date/time columns stored and returned as UTC ISO strings. Frontend display and request conversion should use `resources/js/composables/useDateTimeFormat.ts` with the shared `auth.preferences` timezone and format values.

# Public identifiers

- Models that expose records to the client should keep numeric `id` values internal and expose the model `public_id` as the frontend `id` through Laravel resources.
- Use `App\Models\Concerns\HasPublicId` for models with a `public_id` column so ULIDs are generated automatically and route model binding resolves by `public_id`.
- When seeding through `WithoutModelEvents`, explicitly provide `public_id` values because automatic ULID generation depends on model events.

# Communication registry and seeding

- Define communication channels, providers, credential fields, validation rules, and select options in `App\Support\CommunicationRegistry`. Add definitions there and run `php artisan db:seed --class=CommunicationSeeder`; keep the seeder independent from preference and permission seeders.
- `CommunicationSeeder` syncs registered metadata and creates missing provider records. Preserve existing credentials, activation status, priority, and public IDs when reseeding. Do not automatically delete records absent from the registry. Keep channel, provider, and credential field keys stable.
- Read communication settings and field definitions from seeded database records at runtime. Settings requests may update only credentials, status, and priority; they must not create provider records or change registered metadata.
- Keep credentials encrypted with `encrypted:array`. Never expose saved secrets in resources or flash them into validation input. Blank submitted secrets preserve their saved values.
- Seed Communication test fixtures with `CommunicationSeeder`. Derive catalog counts and provider datasets from `CommunicationRegistry`, locate rows by channel/provider keys, and retain explicit provider fixtures for credential-specific behavior. Cover reseeding, metadata refresh, permission denial, and secret preservation.
- Communication permissions remain in `PermissionRegistry` and are seeded by `UserManagementSeeder`. Do not reference removed per-setting permission seeders. `UserManagementSeeder` also manages default users, roles, and preferences; it is not a provider-only sync command.
