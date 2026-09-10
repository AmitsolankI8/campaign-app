# Communication settings

Open **Settings → Communication** to configure SMS, Voice Call, and Email providers. SMS and Voice Call each support Twilio, Telnyx, and Vonage; Email supports SMTP. Configuration is independent per channel.

Providers are listed in a table for each channel. Use **Credentials** to open a provider's modal and save its configuration. The **Active** checkbox saves immediately; required credentials must be configured before activation. Edit a priority from 1–999 and use the adjacent **Save** button. Lower numbers rank first. Equal priorities use catalog order: Twilio, Telnyx, Vonage. Inactive providers can be saved with incomplete credentials.

Credentials are encrypted in the database using Laravel's application key. Secret fields are never returned to the browser. Leave a saved secret blank to retain it, or enter a replacement to rotate it. SMTP supports STARTTLS, SSL/TLS, and no encryption; username and password are optional for unauthenticated relays.

Access requires `communication.view`; changing configuration requires both `communication.view` and `communication.edit`. Permissions can be granted directly or through roles. The frontend uses the same permissions as the backend.

For existing installations:

```sh
php artisan migrate
php artisan db:seed --class=CommunicationSeeder
```

Fresh installations run `CommunicationSeeder` before `UserManagementSeeder`. Communication permissions are registered in `PermissionRegistry` and seeded by `UserManagementSeeder`, which also manages default users, role permissions, and preferences. Run the provider seeder alone for provider-definition updates; there is no separate Communication permission seeder.

`CommunicationRegistry` defines the channels, providers, credential fields, validation rules, and select options. `CommunicationSeeder` syncs those definitions to the database. Rerunning it updates registered metadata and adds new providers while preserving saved credentials, activation status, priority, and public IDs. The settings page reads existing records, and updates cannot create providers or change their definitions.

To add or change Communication options, edit `app/Support/CommunicationRegistry.php`. Add a channel or provider entry in `channels()`, or add fields to an existing provider using `self::field()`. Declare field type, secret handling, validation rules, and select options in that entry. Keep existing channel, provider, and field keys stable so saved configuration remains associated with them. Entry order controls default priority for new providers and ordering when priorities match.

Then sync the registry:

```sh
php artisan db:seed --class=CommunicationSeeder
```

The seeder needs no edits when adding definitions. It does not delete rows removed from the registry; retire existing providers explicitly to preserve their saved configuration. Preferences continue to use `PreferenceRegistry` and `PreferenceSeeder`.

Communication tests seed their fixtures with `CommunicationSeeder`. Provider datasets and counts come from `CommunicationRegistry`, so new entries join the required-field and inactive-draft checks automatically. Assertions identify channels and providers by their keys rather than assuming table positions. Credential-specific fixtures, such as Twilio token rotation, remain explicit. The tests also cover metadata refresh, repeated seeding, ULIDs without model events, preservation of records outside the registry, and permissions inherited through roles.

After changing registry definitions, run:

```sh
php artisan test --compact tests/Feature/Settings/CommunicationSettingsTest.php
```

Add provider-specific validation cases when introducing rules that the general required-field and draft checks do not cover.

This feature stores provider settings. It does not send messages, make calls, test remote credentials, replace the application's mail transport, or implement delivery failover. A future delivery integration must select active providers by channel and priority.

Provider credential references: [Twilio authentication](https://www.twilio.com/docs/usage/requests-to-twilio), [Telnyx Voice API](https://developers.telnyx.com/docs/voice/programmable-voice/voice-api-fundamentals), and [Vonage Voice setup](https://developer.vonage.com/en/voice/voice-api/getting-started).
