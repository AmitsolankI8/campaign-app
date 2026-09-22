# Local development workflow

- The application is currently in active local development, and module flows are still subject to change. Do not create or modify automated test cases for a new feature or module unless the user explicitly requests tests. Existing tests may still be run when useful for verification.
- The local database is refreshed and seeded after schema changes. When changing an existing table, edit its original create migration instead of creating an additional alter-table migration.
- Create a new migration file only when a genuinely new table is required. Add new columns, indexes, constraints, or other changes for an existing table directly to that table's original create migration.
- Revisit these temporary testing and migration policies before the application is deployed or requires forward-only schema upgrades.

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
- Once-off campaign tabs stay in this order: Summary, Contacts, Upload Contacts, Schedule. Each tab owns a route, controller, and Inertia page: `OnceOffCampaignController@show`, `OnceOffCampaignContactController@index`, `OnceOffCampaignContactImportController@index`, and `OnceOffCampaignScheduleController@show`. Load only the current section's data, plus the shared first-attempt scheduled date needed by Basic details.
- Use the persistent `OnceOffCampaignLayout.vue` for all once-off pages. Keep the campaign header, basic-details sidebar, and tabs visible on nested upload details. Define tab destinations and active pages in `useCampaignTabs.ts` using Wayfinder routes, not a `?tab=` query. Upload details belong to Upload Contacts.
- When tests are explicitly requested, cover campaign changes with feature tests for permission denial/grants, datatable search/filter validation, resource payloads, default status, update restrictions, and type-specific show routing.

# Campaign type models

- Keep `App\Models\Campaign` as the shared model for all types, with an explicit `campaigns` table, common attributes, enum casts, public IDs, and shared CRUD. Campaign subclasses use the same table; do not create separate campaign identity tables for each type.
- Put type-specific models in `App\Models\Campaigns`, extending `Campaign`. Use the existing `OnceOffCampaign` for once-off operations. When implementing ongoing and batch-processing behavior, add `OngoingCampaign` and `BatchProcessingCampaign` in that namespace and follow the same structure.
- Each subclass must apply a global scope with a qualified `campaign_type` column matching its `CampaignType` case, default to that type, retain `CampaignStatus::DEFAULT`, and reject model saves with a different type. Keep generic `Campaign` queries unfiltered so shared screens can access all types. Do not bypass the type scope or change `campaign_type` in type-specific operations, including bulk updates that bypass model events.
- Type-hint the matching subclass in type-specific controllers, services, and request model annotations. Use its scoped queries for lookups and transaction locks. Binding a once-off public ID through an ongoing or batch-processing controller, or vice versa, must return 404. Keep permission authorization and scoped child bindings in place.
- A record loaded through `Campaign` remains a `Campaign` instance even when its enum identifies a specific type. Resolve it through the matching subclass query before passing it into a type-specific service; do not assume automatic subclass conversion. Keep `CampaignController@show` as the route dispatcher.
- Define type-specific relationships on the subclass, using names such as `contacts()`, `contactImports()`, `schedules()`, and `firstSchedule()` where applicable. Explicitly use `campaign_id` for child foreign keys; subclass names must not cause Eloquent to infer keys such as `once_off_campaign_id`. Child models should resolve their `campaign()` relationship to the matching subclass.
- Keep `Campaign::firstOnceOffSchedule()` as the existing shared read relation for `CampaignResource` and Basic details. `OnceOffCampaign::firstSchedule()` delegates to it. This shared projection does not move once-off scheduling or contact operations back onto `Campaign`; load only the first attempt outside the Schedule tab.
- Keep once-off scheduling, contact staging, sync planning, syncing, and status changes on `OnceOffCampaign`, including `SaveOnceOffCampaignSchedules`, `StageCampaignContactUpload`, `CampaignContactSyncPlan`, `SyncOnceOffCampaignContactImport`, and `ChangeOnceOffCampaignStatus`. Re-query the subclass under the campaign lock before writes so stale models cannot bypass stored type or status restrictions. Lock the campaign before its child records.
- Provide a matching factory under `Database\Factories\Campaigns` for each implemented subclass. Its `factory()` must return the subclass with the correct type and draft default. Reuse common fixture fields where useful, but explicitly override any reused once-off type default for ongoing or batch-processing factories. Keep `CampaignFactory` available for shared and mixed-type tests.
- Implement each future type's controllers, services, child tables, pages, and lifecycle rules when its requirements are defined. Reuse shared infrastructure such as permissions, datatables, public resources, and date/time conversion; keep once-off attempt, upload, replacement, and launch rules specific to once-off campaigns unless explicitly required for another type. Wire each type through `CampaignType::showRouteName()` and the matching frontend type keys.
- When tests are explicitly requested, cover each subclass with Pest tests for shared-table persistence, factory/model defaults, filtered queries, public-ID binding, wrong-type save rejection, relationship foreign keys and parent types, and stale stored-type checks under transaction locks. Retain shared CRUD, resource, cross-campaign isolation, and direct/inherited permission coverage as new types are implemented.

# Once-off campaign schedules

- The Schedule tab uses `OnceOffCampaignScheduleController@show` and `@update`. A campaign can save multiple attempts in `once_off_campaign_schedules`; saving requires at least one complete attempt. Viewing an unscheduled campaign must not create placeholder database records.
- A new unsaved schedule starts with one first attempt whose `scheduled_at` and channel are blank. Previously saved attempts retain their saved values when loaded. Keep the first form row present and allow follow-ups to be removed.
- Do not provide manual sorting controls. Each follow-up must have a `scheduled_at` strictly later than the immediately preceding attempt, enforced in frontend and backend validation. The 24-hour interval is a default, not a minimum required gap.
- Add follow-up copies the previous attempt's channel and defaults its time to exactly 24 hours later. Handle each field independently: a missing previous channel or date stays blank in the new row. Allow adding incomplete follow-ups; validate required values when saving.
- Persist `attempt_number` as the server-assigned, contiguous, one-based sequence. Do not accept client-supplied attempt counts or campaign IDs. Preserve the public IDs of retained attempts when editing or removing follow-ups.
- Require `campaigns.view` to view schedules and both `campaigns.view` and `campaigns.edit` to save. Use matching `hasPermissions([...], true)` checks for editing controls and handlers. Scope all submitted schedule public IDs to the campaign and reject other campaign types.
- Read available channel keys and labels from seeded `CommunicationProvider` records at runtime, deduplicated by channel. A schedule selects a channel, not a provider; never expose provider credentials in schedule props. Use `CommunicationSeeder` and derive channel fixtures from `CommunicationRegistry` in tests.
- Use `useDateTimeFormat.ts` with shared preferences for input conversion and display. Submit UTC ISO values, store UTC dates, and expose UTC ISO strings through resources. Expose public IDs only.
- Save the whole submitted schedule transactionally through `SaveOnceOffCampaignSchedules`, locking the campaign first. Recheck retained IDs under the lock, remove omitted attempts, and renumber the remaining attempts. Failures must roll back deletions, edits, inserts, and numbering changes together.
- In once-off Basic details, show `Scheduled At` directly below Campaign type. Use the saved attempt with `attempt_number = 1` through `Campaign::firstOnceOffSchedule`, shared as `campaign.scheduled_at`; show `--` when absent. Keep this field available on every once-off tab and nested upload details without loading the full schedule outside its own tab.
- When tests are explicitly requested, cover schedule authentication, direct/inherited permissions, campaign isolation, required fields, chronology, channel validation, public resources, saving/repeated updates/removals, transactional rollback, stale IDs, and Basic details dates with Pest feature tests. Keep the fresh-local-schema policy: edit the original create migration for schema changes; add migrations only for new tables.

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
- When tests are explicitly requested, cover staging versus active contacts, validation without writes, private downloads, campaign isolation, permissions (direct and inherited), datatable behavior, public resource payloads, duplicate modes, partial/repeated syncs, and replacement safeguards with Pest feature tests.
- During the current fresh-local-database development phase, fold schema changes into the original create migrations; add a migration only for a new table. Revisit this before changing any deployed schema.

# Communication registry and seeding

- Define communication channels, providers, credential fields, validation rules, and select options in `App\Support\CommunicationRegistry`. Add definitions there and run `php artisan db:seed --class=CommunicationSeeder`; keep the seeder independent from preference and permission seeders.
- `CommunicationSeeder` syncs registered metadata and creates missing provider records. Preserve existing credentials, activation status, priority, and public IDs when reseeding. Do not automatically delete records absent from the registry. Keep channel, provider, and credential field keys stable.
- Read communication settings and field definitions from seeded database records at runtime. Settings requests may update only credentials, status, and priority; they must not create provider records or change registered metadata.
- Keep credentials encrypted with `encrypted:array`. Never expose saved secrets in resources or flash them into validation input. Blank submitted secrets preserve their saved values.
- When tests are explicitly requested, seed Communication test fixtures with `CommunicationSeeder`. Derive catalog counts and provider datasets from `CommunicationRegistry`, locate rows by channel/provider keys, and retain explicit provider fixtures for credential-specific behavior. Cover reseeding, metadata refresh, permission denial, and secret preservation.
- Communication permissions remain in `PermissionRegistry` and are seeded by `UserManagementSeeder`. Do not reference removed per-setting permission seeders. `UserManagementSeeder` also manages default users, roles, and preferences; it is not a provider-only sync command.
