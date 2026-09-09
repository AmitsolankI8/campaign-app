<?php

namespace App\Support;

use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Cache;

class UserPreferences
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return Cache::remember(
            self::cacheKey((int) $user->getKey()),
            now()->addWeek(),
            fn (): array => self::resolve($user),
        );
    }

    public static function forgetForUserId(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        Cache::forget(self::cacheKey($userId));
    }

    private static function cacheKey(int $userId): string
    {
        return "user-preferences:{$userId}";
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolve(User $user): array
    {
        $preferences = UserPreference::query()
            ->with(['country', 'timezone', 'language', 'numberFormat', 'dateFormat', 'timeFormat'])
            ->where('user_id', $user->getKey())
            ->first();

        return [
            'values' => $preferences?->only(PreferenceOptions::FIELDS),
            'country' => self::simplePreference($preferences?->country),
            'timezone' => self::simplePreference($preferences?->timezone),
            'language' => self::simplePreference($preferences?->language),
            'formats' => [
                'number' => self::formatPreference($preferences?->numberFormat),
                'date' => self::formatPreference($preferences?->dateFormat),
                'time' => self::formatPreference($preferences?->timeFormat),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, identifier: string, display_name: string, short_code: string}|null
     */
    private static function simplePreference(
        PreferenceCountry|PreferenceTimezone|PreferenceLanguage|null $preference,
    ): ?array {
        if ($preference === null) {
            return null;
        }

        return [
            'id' => $preference->id,
            'name' => $preference->name,
            'identifier' => $preference->identifier,
            'display_name' => $preference->display_name,
            'short_code' => $preference->short_code,
        ];
    }

    /**
     * @return array{id: int, type: string, name: string, display_name: string, format: string, client_format: string, example: string}|null
     */
    private static function formatPreference(?PreferenceFormat $preference): ?array
    {
        if ($preference === null) {
            return null;
        }

        return [
            'id' => $preference->id,
            'type' => $preference->type,
            'name' => $preference->name,
            'display_name' => $preference->display_name,
            'format' => $preference->format,
            'client_format' => self::clientFormat($preference),
            'example' => $preference->example,
        ];
    }

    private static function clientFormat(PreferenceFormat $preference): string
    {
        if ($preference->type === PreferenceFormat::TYPE_DATE || $preference->type === PreferenceFormat::TYPE_TIME) {
            return self::toDayjsFormat($preference->format);
        }

        return $preference->format;
    }

    private static function toDayjsFormat(string $format): string
    {
        return strtr($format, [
            'Y' => 'YYYY',
            'y' => 'YY',
            'm' => 'MM',
            'n' => 'M',
            'd' => 'DD',
            'j' => 'D',
            'H' => 'HH',
            'G' => 'H',
            'h' => 'hh',
            'g' => 'h',
            'i' => 'mm',
            's' => 'ss',
            'A' => 'A',
            'a' => 'a',
        ]);
    }
}
