<?php

namespace App\Enums;

enum ContactImportStatus: int
{
    case Pending = 1;
    case Synced = 2;

    public const DEFAULT = self::Pending->value;

    public function key(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Synced => 'synced',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Synced => __('Synced'),
        };
    }

    /** @return list<int> */
    public static function values(): array
    {
        return array_map(fn (self $status): int => $status->value, self::cases());
    }

    /** @return list<array{value: int, key: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $status): array => $status->toArray(), self::cases());
    }

    /** @return array{value: int, key: string, label: string} */
    public function toArray(): array
    {
        return ['value' => $this->value, 'key' => $this->key(), 'label' => $this->label()];
    }
}
