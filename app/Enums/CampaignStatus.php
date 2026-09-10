<?php

namespace App\Enums;

enum CampaignStatus: int
{
    case Draft = 1;
    case Launched = 2;
    case Running = 3;
    case Paused = 4;
    case Cancelled = 5;

    public const DEFAULT = self::Draft->value;

    public function key(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Launched => 'launched',
            self::Running => 'running',
            self::Paused => 'paused',
            self::Cancelled => 'cancelled',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Launched => __('Launched'),
            self::Running => __('Running'),
            self::Paused => __('Paused'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): int => $status->value, self::cases());
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (self $status): string => $status->key(), self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function labelsByValue(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function labelsByKey(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->key() => $status->label()])
            ->all();
    }

    /**
     * @return list<array{value: int, key: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => $status->toArray(),
            self::cases(),
        );
    }

    /**
     * @return array{value: int, key: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'key' => $this->key(),
            'label' => $this->label(),
        ];
    }
}
