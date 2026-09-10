<?php

namespace App\Enums;

enum CampaignType: int
{
    case OnceOff = 1;
    case Ongoing = 2;
    case BatchProcessing = 3;

    public function key(): string
    {
        return match ($this) {
            self::OnceOff => 'once_off',
            self::Ongoing => 'ongoing',
            self::BatchProcessing => 'batch_processing',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OnceOff => __('Once-Off'),
            self::Ongoing => __('Ongoing'),
            self::BatchProcessing => __('Batch Processing'),
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): int => $type->value, self::cases());
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (self $type): string => $type->key(), self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function labelsByValue(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function labelsByKey(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->key() => $type->label()])
            ->all();
    }

    /**
     * @return list<array{value: int, key: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => $type->toArray(),
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
