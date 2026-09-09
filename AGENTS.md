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
