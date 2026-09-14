<?php

namespace App\Enums;

enum ContactUploadRowStatus: int
{
    case Pending = 1;
    case Added = 2;
    case Updated = 3;
    case Skipped = 4;
    case Failed = 5;

    public const DEFAULT = self::Pending->value;

    public function key(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Added => 'added',
            self::Updated => 'updated',
            self::Skipped => 'skipped',
            self::Failed => 'failed',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Added => __('Added'),
            self::Updated => __('Updated'),
            self::Skipped => __('Skipped'),
            self::Failed => __('Failed'),
        };
    }

    /** @return list<int> */
    public static function values(): array
    {
        return array_map(fn (self $item): int => $item->value, self::cases());
    }

    /** @return list<array{value: int, key: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $item): array => $item->toArray(), self::cases());
    }

    /** @return array{value: int, key: string, label: string} */
    public function toArray(): array
    {
        return ['value' => $this->value, 'key' => $this->key(), 'label' => $this->label()];
    }
}
