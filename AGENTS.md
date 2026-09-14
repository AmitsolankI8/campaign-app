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

# Integer-backed types

- When a model stores a multi-option type/status/category as an integer, define a PHP backed enum for that field instead of putting type constants and labels directly on the model.
- Keep the database column integer-backed and cast it to the enum on the Eloquent model. Put the enum value, stable frontend key, label, options list, and allowed values on the enum.
- Laravel resources should expose a single nested payload such as `type: { value, key, label }`. Do not add one boolean per type, such as `is_once_off`, because that grows poorly as new types are added.
- Frontend modules should keep one matching constants/types file for stable keys, such as `CAMPAIGN_TYPE_KEY.onceOff = 'once_off'`, and compare against `record.type.key`. Do not compare labels, and avoid scattering raw integer checks through Vue files.
- When adding a new type later, update the backend enum and the matching frontend constants/types file first, then wire only the screens that need type-specific behavior.

# Campaign module

- Keep `campaign_type` and `status` integer-backed. Use `App\Enums\CampaignType` and `App\Enums\CampaignStatus`, cast both fields on `Campaign`, expose both as `{ value, key, label }`, and keep matching keys/types in `resources/js/pages/campaigns/types.ts`.
- Campaign status defaults to draft. Keep the default centralized through `CampaignStatus::DEFAULT` and mirror it in both the migration and `Campaign` model attributes.
- Keep `CampaignController` responsible for index/create/store/edit/update and use its `show` method only as a dispatcher to the type-specific show route.
- Put type-specific campaign view behavior in dedicated controllers and Vue pages. Add new routes through `CampaignType::showRouteName()` so Laravel resources can expose a single `show_url` and Vue does not need to build type-based URLs.
- Campaign index must use the shared datatable. Search only `name` and `short_note`; use extra filters for campaign type and status. The index action should show only a view action unless campaign actions are explicitly expanded.
- Campaign create accepts `name`, `short_note`, and `campaign_type`; edit accepts only `name` and `short_note`. Do not allow edit flows to change campaign type or status until a status workflow is explicitly added.
- Once-off campaign tabs stay in this order: Summary, Contacts, Upload Contacts, Schedule. Each tab owns a route, controller, and Inertia page: `OnceOffCampaignController@show`, `OnceOffCampaignContactController@index`, `OnceOffCampaignContactImportController@index`, and `OnceOffCampaignScheduleController@show`. Load only the current section's data; Schedule remains a placeholder until its workflow is specified.
- Use the persistent `OnceOffCampaignLayout.vue` for all once-off pages. Keep the campaign header, basic-details sidebar, and tabs visible on nested upload details. Define tab destinations and active pages in `useCampaignTabs.ts` using Wayfinder routes, not a `?tab=` query. Upload details belong to Upload Contacts.
- Cover campaign changes with feature tests for permission denial/grants, datatable search/filter validation, resource payloads, default status, update restrictions, and type-specific show routing.

# Once-off campaign contacts

- Active contacts belong to a campaign and contain required first name and international phone number, with optional last name and email. Use `CampaignContactRules` for manual, file, and sync validation. Phone numbers start with `+` and a country code and contain 7–15 digits; compare their digits-only `normalized_number` within the same campaign.
- Both manual entries and files first create an Uploaded Contacts batch (`OnceOffCampaignContactImport`) and staged `OnceOffCampaignContactUploadRow` records through `StageCampaignContactUpload`. Only syncing creates or updates active `OnceOffCampaignContact` records. Preserve batch source, mode, uploader, row outcomes, and timestamps.
- Use the shared datatable for Contacts, Uploaded Contacts, and upload rows, with the existing `contacts`, `imports`, and `rows` query namespaces. Require `campaigns.view` for viewing campaign contacts; require both `campaigns.view` and `campaigns.edit` for upload listing, staging, validation, detail, download, and sync. Enforce campaign type and scoped upload bindings on the backend and matching frontend visibility.
- Accept CSV, XLSX, and XLS files up to 2 MB, 5,000 contact rows, and 50 columns. Use the first Excel worksheet. Require `first_name` and `number` headers; report invalid cells, duplicate numbers within the file, duplicate required/optional contact headers, and formulas without evaluating them.
- Valid files save directly to Uploaded Contacts. Open the file error modal only for validation errors and show only affected rows with original row numbers and cell errors, plus file-level errors. Reject the entire invalid file before creating database records or saving its original. Revalidate at store time; never trust an earlier validation response.
- Store original files on the private local disk and download them only through the authorized, campaign-scoped controller. Do not expose storage paths. Preserve public IDs and enum resource payloads for uploads and rows.
- Append adds new numbers and skips existing contacts; Update adds new numbers and updates matches while preserving saved optional values when uploaded values are blank. Allow individual, selected, and all-remaining row syncs for these modes. Keep prior values, errors, and outcomes; completed rows must not be processed again.
- Replace requires a file, a draft campaign, and an entirely pending upload. Apply the entire upload together after confirming the current sync-plan fingerprint. Reject partial or stale replacement requests. Soft-delete contacts absent from the replacement, record the responsible upload, and retain history.
- Keep sync operations transactional and lock the campaign before its upload. Recheck matches and validation at sync time, retain failed rows for retry, and derive pending/partially-synced/synced status from remaining rows. Failed replacements must roll back all contact and row changes.
- Cover staging versus active contacts, validation without writes, private downloads, campaign isolation, permissions (direct and inherited), datatable behavior, public resource payloads, duplicate modes, partial/repeated syncs, and replacement safeguards with Pest feature tests.
- During the current fresh-local-database development phase, fold schema changes into the original create migrations; add a migration only for a new table. Revisit this before changing any deployed schema.

# Communication registry and seeding

- Define communication channels, providers, credential fields, validation rules, and select options in `App\Support\CommunicationRegistry`. Add definitions there and run `php artisan db:seed --class=CommunicationSeeder`; keep the seeder independent from preference and permission seeders.
- `CommunicationSeeder` syncs registered metadata and creates missing provider records. Preserve existing credentials, activation status, priority, and public IDs when reseeding. Do not automatically delete records absent from the registry. Keep channel, provider, and credential field keys stable.
- Read communication settings and field definitions from seeded database records at runtime. Settings requests may update only credentials, status, and priority; they must not create provider records or change registered metadata.
- Keep credentials encrypted with `encrypted:array`. Never expose saved secrets in resources or flash them into validation input. Blank submitted secrets preserve their saved values.
- Seed Communication test fixtures with `CommunicationSeeder`. Derive catalog counts and provider datasets from `CommunicationRegistry`, locate rows by channel/provider keys, and retain explicit provider fixtures for credential-specific behavior. Cover reseeding, metadata refresh, permission denial, and secret preservation.
- Communication permissions remain in `PermissionRegistry` and are seeded by `UserManagementSeeder`. Do not reference removed per-setting permission seeders. `UserManagementSeeder` also manages default users, roles, and preferences; it is not a provider-only sync command.
