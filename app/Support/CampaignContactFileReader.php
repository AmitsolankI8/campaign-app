<?php

namespace App\Support;

use Generator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Throwable;

class CampaignContactFileReader
{
    public const MAX_ROWS = 5000;

    /** @return list<array<string, mixed>> */
    public function read(UploadedFile $file): array
    {
        $preview = $this->preview($file);
        if ($preview['error_count'] > 0) {
            throw ValidationException::withMessages(['file' => __('Correct the highlighted file errors before saving this upload.')]);
        }

        return $preview['contacts'];
    }

    /** @return array{headers: list<string>, columns: list<string>, rows: list<array<string, mixed>>, errors: list<string>, error_count: int, contacts: list<array<string, mixed>>} */
    public function preview(UploadedFile $file): array
    {
        try {
            return $this->validateRows($this->rows($file));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => __('This file could not be read. Upload a valid, unprotected CSV, XLSX, or XLS file.')]);
        }
    }

    /** @return Generator<int, array<mixed>> */
    private function rows(UploadedFile $file): Generator
    {
        if (Str::lower($file->getClientOriginalExtension()) === 'csv') {
            $handle = fopen($file->getPathname(), 'rb');

            if ($handle === false) {
                throw ValidationException::withMessages(['file' => __('This CSV could not be opened.')]);
            }

            try {
                $line = fgets($handle);
                rewind($handle);
                $delimiter = ',';
                $mostColumns = 0;

                foreach ([',', ';', "\t"] as $candidate) {
                    $columns = count(str_getcsv($line === false ? '' : $line, $candidate, '"', ''));
                    if ($columns > $mostColumns) {
                        $delimiter = $candidate;
                        $mostColumns = $columns;
                    }
                }

                $row = 0;
                while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                    yield ++$row => $values;
                }
            } finally {
                fclose($handle);
            }

            return;
        }

        $reader = IOFactory::createReader(Str::lower($file->getClientOriginalExtension()) === 'xlsx' ? 'Xlsx' : 'Xls');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= CampaignContactFileReader::MAX_ROWS + 2
                    && Coordinate::columnIndexFromString($columnAddress) <= 50;
            }
        });

        $sheets = $reader->listWorksheetInfo($file->getPathname());
        if ($sheets === [] || $sheets[0]['totalRows'] > self::MAX_ROWS + 1 || $sheets[0]['totalColumns'] > 50) {
            throw ValidationException::withMessages(['file' => __('Use a first worksheet with at most 5,000 contacts and 50 columns.')]);
        }

        $reader->setLoadSheetsOnly($sheets[0]['worksheetName']);
        $spreadsheet = $reader->load($file->getPathname());

        try {
            $sheet = $spreadsheet->getSheet(0);
            foreach ($sheet->getRowIterator() as $row) {
                $values = [];
                foreach ($row->getCellIterator('A', $sheet->getHighestDataColumn()) as $cell) {
                    // Read the literal value; validation marks formulas without evaluating them.
                    $values[] = $cell->getValue();
                }
                yield $row->getRowIndex() => $values;
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @param  Generator<int, array<mixed>>  $rows
     * @return array{headers: list<string>, columns: list<string>, rows: list<array<string, mixed>>, errors: list<string>, error_count: int, contacts: list<array<string, mixed>>}
     */
    private function validateRows(Generator $rows): array
    {
        $headers = [];
        $columns = [];
        $previewRows = [];
        $contacts = [];
        $errors = [];
        $numberRows = [];

        foreach ($rows as $rowNumber => $values) {
            if ($rowNumber > self::MAX_ROWS + 1 || count($values) > 50) {
                throw ValidationException::withMessages(['file' => __('Use at most 5,000 contact rows and 50 columns.')]);
            }

            $values = array_values(array_map(function (mixed $value): string {
                if (is_float($value) && floor($value) === $value) {
                    return sprintf('%.0f', $value);
                }

                return mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8');
            }, $values));

            if ($rowNumber === 1) {
                $headers = $values;
                $columns = array_map(fn (string $value): string => Str::of($value)->replace("\xEF\xBB\xBF", '')->trim()->lower()->replaceMatches('/[\\s-]+/', '_')->toString(), $values);
                foreach (['first_name', 'number'] as $required) {
                    if (! in_array($required, $columns, true)) {
                        $errors[] = __('Missing required column: :column.', ['column' => $required]);
                    }
                }
                foreach (array_keys(CampaignContactRules::rules()) as $field) {
                    if (count(array_keys($columns, $field, true)) > 1) {
                        $errors[] = __('The :field column appears more than once.', ['field' => $field]);
                    }
                }

                continue;
            }

            if (array_filter($values, fn (string $value): bool => trim($value) !== '') === []) {
                continue;
            }

            if (count($values) > count($headers)) {
                $errors[] = __('Row :row has more cells than the header. Give every column a heading or remove the extra cells.', ['row' => $rowNumber]);
            }

            $contact = [];
            foreach (array_keys(CampaignContactRules::rules()) as $field) {
                $position = array_search($field, $columns, true);
                $value = $position === false ? '' : trim($values[$position] ?? '');
                $contact[$field] = $value === '' ? null : $value;
            }

            $validator = Validator::make($contact, CampaignContactRules::rules(), CampaignContactRules::messages());
            $previewRow = new ContactFilePreviewRow($rowNumber, $values);
            foreach ($validator->errors()->messages() as $field => $messages) {
                $position = array_search($field, $columns, true);
                if ($position !== false) {
                    foreach ($messages as $message) {
                        $previewRow->addError($position, $message);
                    }
                }
            }
            foreach ($values as $position => $value) {
                if (str_starts_with(trim($value), '=')) {
                    $previewRow->addError($position, __('Replace formulas with plain values.'));
                }
            }

            $normalized = preg_replace('/\\D/', '', $contact['number'] ?? '');
            $numberPosition = array_search('number', $columns, true);
            if ($normalized !== '' && $numberPosition !== false) {
                if (isset($numberRows[$normalized])) {
                    $previous = $numberRows[$normalized];
                    $previewRow->addError($numberPosition, __('Duplicate number; also appears on row :row.', ['row' => $previewRows[$previous]->rowNumber]));
                    $previewRows[$previous]->addError($numberPosition, __('Duplicate number; also appears on row :row.', ['row' => $rowNumber]));
                } else {
                    $numberRows[$normalized] = count($previewRows);
                }
            }

            $previewRows[] = $previewRow;
            $contacts[] = [...$contact, 'row_number' => $rowNumber, 'normalized_number' => $normalized];
        }

        if ($contacts === []) {
            $errors[] = __('The file must contain at least one contact below the header row.');
        }

        $errorCount = count($errors);
        $publicRows = [];
        foreach ($previewRows as $row) {
            foreach ($row->errors as $messages) {
                $errorCount += count($messages);
            }
            // Keep column-indexed errors an object even when the first cell is the only error.
            $publicRows[] = $row->toArray();
        }

        return ['headers' => $headers, 'columns' => $columns, 'rows' => $publicRows, 'errors' => $errors, 'error_count' => $errorCount, 'contacts' => $contacts];
    }
}
