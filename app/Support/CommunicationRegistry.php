<?php

namespace App\Support;

/**
 * @phpstan-type CredentialField array{key: string, label: string, type: string, secret: bool, required: bool, rules: list<string>, options: list<array{value: string, label: string}>}
 * @phpstan-type ProviderDefinition array{name: string, fields: list<CredentialField>}
 * @phpstan-type ChannelDefinition array{name: string, providers: array<string, ProviderDefinition>}
 */
class CommunicationRegistry
{
    /** @return array<string, ChannelDefinition> */
    public static function channels(): array
    {
        $from = self::field('from', 'From phone number');
        $apiKey = self::field('api_key', 'API key', type: 'password', secret: true);
        $twilio = [
            'name' => 'Twilio',
            'fields' => [
                self::field('account_sid', 'Account SID'),
                self::field('auth_token', 'Auth token', type: 'password', secret: true),
                $from,
            ],
        ];

        return [
            'sms' => [
                'name' => 'SMS',
                'providers' => [
                    'twilio' => $twilio,
                    'telnyx' => ['name' => 'Telnyx', 'fields' => [$apiKey, $from]],
                    'vonage' => [
                        'name' => 'Vonage',
                        'fields' => [
                            $apiKey,
                            self::field('api_secret', 'API secret', type: 'password', secret: true),
                            self::field('from', 'Sender ID or phone number'),
                        ],
                    ],
                ],
            ],
            'voice' => [
                'name' => 'Voice Call',
                'providers' => [
                    'twilio' => $twilio,
                    'telnyx' => [
                        'name' => 'Telnyx',
                        'fields' => [$apiKey, $from, self::field('connection_id', 'Voice application ID')],
                    ],
                    'vonage' => [
                        'name' => 'Vonage',
                        'fields' => [
                            self::field('application_id', 'Application ID'),
                            self::field('private_key', 'Private key (PEM)', type: 'textarea', secret: true, rules: ['string', 'max:16000']),
                            $from,
                        ],
                    ],
                ],
            ],
            'email' => [
                'name' => 'Email',
                'providers' => [
                    'smtp' => [
                        'name' => 'SMTP',
                        'fields' => [
                            self::field('host', 'SMTP host'),
                            self::field('port', 'SMTP port', type: 'number', rules: ['integer', 'between:1,65535']),
                            self::field('encryption', 'Encryption', type: 'select', rules: ['in:tls,ssl,none'], options: [
                                ['value' => 'tls', 'label' => 'STARTTLS'],
                                ['value' => 'ssl', 'label' => 'SSL/TLS'],
                                ['value' => 'none', 'label' => 'None'],
                            ]),
                            self::field('username', 'Username', required: false),
                            self::field('password', 'Password', type: 'password', secret: true, required: false),
                            self::field('from_address', 'From email address', type: 'email', rules: ['email', 'max:255']),
                            self::field('from_name', 'From name'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return list<array{channel: string, channel_name: string, channel_position: int, provider: string, name: string, position: int, fields: list<CredentialField>}> */
    public static function providers(): array
    {
        $records = [];
        $channelPosition = 0;
        foreach (self::channels() as $channel => $definition) {
            $channelPosition++;
            $position = 0;
            foreach ($definition['providers'] as $provider => $attributes) {
                $records[] = [
                    'channel' => $channel,
                    'channel_name' => $definition['name'],
                    'channel_position' => $channelPosition,
                    'provider' => $provider,
                    'name' => $attributes['name'],
                    'position' => ++$position,
                    'fields' => $attributes['fields'],
                ];
            }
        }

        return $records;
    }

    /**
     * @param  list<string>  $rules
     * @param  list<array{value: string, label: string}>  $options
     * @return CredentialField
     */
    private static function field(
        string $key,
        string $label,
        string $type = 'text',
        bool $secret = false,
        bool $required = true,
        array $rules = ['string', 'max:255'],
        array $options = [],
    ): array {
        return compact('key', 'label', 'type', 'secret', 'required', 'rules', 'options');
    }
}
