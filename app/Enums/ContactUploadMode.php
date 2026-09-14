<?php

namespace App\Enums;

enum ContactUploadMode: int
{
    case Append = 1;
    case Update = 2;
    case Replace = 3;

    public const DEFAULT = self::Append->value;

    public function key(): string
    {
        return match ($this) {
            self::Append => 'append',
            self::Update => 'update',
            self::Replace => 'replace',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Append => __('Add new contacts'),
            self::Update => __('Add and update existing'),
            self::Replace => __('Replace campaign contacts'),
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
