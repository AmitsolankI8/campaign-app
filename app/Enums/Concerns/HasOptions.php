<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait HasOptions
{
    public function key(): string
    {
        return Str::snake($this->name);
    }

    public function label(): string
    {
        return __(Str::headline($this->name));
    }

    /** @return array{value: int, key: string, label: string} */
    public function toArray(): array
    {
        return ['value' => $this->value, 'key' => $this->key(), 'label' => $this->label()];
    }

    /** @return list<int> */
    public static function values(): array
    {
        return array_map(fn (self $case): int => $case->value, self::cases());
    }

    /** @return list<array{value: int, key: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case): array => $case->toArray(), self::cases());
    }
}
