<?php

namespace App\Support;

class ContactFilePreviewRow
{
    /** @param list<string> $values */
    public function __construct(public int $rowNumber, public array $values) {}

    /** @var array<int, list<string>> */
    public array $errors = [];

    public function addError(int $column, string $message): void
    {
        $this->errors[$column][] = $message;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['row_number' => $this->rowNumber, 'values' => $this->values, 'errors' => (object) $this->errors];
    }
}
