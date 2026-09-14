<?php

namespace App\Enums;

enum ContactUploadSource: int
{
    case Manual = 1;
    case File = 2;

    public const DEFAULT = self::File->value;

    public function key(): string
    {
        return match ($this) {
            self::Manual => 'manual',
            self::File => 'file',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('Manual entry'),
            self::File => __('File upload'),
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
