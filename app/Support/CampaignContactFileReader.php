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

    /** @return list<array{first_name: string, last_name: string|null, number: string, email: string|null}> */
    public function read(UploadedFile $file): array
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
                    // Never evaluate formulas in uploaded workbooks.
                    if ($cell->isFormula()) {
                        throw ValidationException::withMessages(['file' => __('Row :row contains a formula. Replace formulas with plain values.', ['row' => $row->getRowIndex()])]);
                    }
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
     * @return list<array{first_name: string, last_name: string|null, number: string, email: string|null}>
     */
    private function validateRows(Generator $rows): array
    {
        $headers = null;
        $contacts = [];
        $errors = [];

        foreach ($rows as $rowNumber => $values) {
            if ($rowNumber > self::MAX_ROWS + 1) {
                throw ValidationException::withMessages(['file' => __('Each file may contain at most 5,000 rows after the header.')]);
            }

            $values = array_map(function (mixed $value): string {
                if (is_float($value) && floor($value) === $value) {
                    return sprintf('%.0f', $value);
                }

                return trim((string) $value);
            }, $values);

            if ($headers === null) {
                $headers = array_map(fn (string $value): string => Str::of($value)->replace("\xEF\xBB\xBF", '')->trim()->lower()->replaceMatches('/[\s-]+/', '_')->toString(), $values);
                foreach (['first_name', 'number'] as $required) {
                    if (! in_array($required, $headers, true)) {
                        throw ValidationException::withMessages(['file' => __('The first row must include first_name and number headers. Optional headers: last_name, email.')]);
                    }
                }
                foreach (array_keys(CampaignContactRules::rules()) as $field) {
                    if (count(array_keys($headers, $field, true)) > 1) {
                        throw ValidationException::withMessages(['file' => __('The :field header appears more than once.', ['field' => $field])]);
                    }
                }

                continue;
            }

            if (array_filter($values, fn (string $value): bool => $value !== '') === []) {
                continue;
            }

            $contact = [];
            foreach (array_keys(CampaignContactRules::rules()) as $field) {
                $position = array_search($field, $headers, true);
                $value = $position === false ? '' : ($values[$position] ?? '');
                $contact[$field] = $value === '' ? null : $value;
            }

            $validator = Validator::make($contact, CampaignContactRules::rules());
            if ($validator->fails()) {
                $errors[] = __('Row :row: :message', ['row' => $rowNumber, 'message' => implode(' ', $validator->errors()->all())]);
                if (count($errors) >= 10) {
                    break;
                }
            } else {
                /** @var array{first_name: string, last_name: string|null, number: string, email: string|null} $contact */
                $contacts[] = $contact;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => implode(' ', $errors)]);
        }
        if ($contacts === []) {
            throw ValidationException::withMessages(['file' => __('The file must contain at least one contact below the header row.')]);
        }

        return $contacts;
    }
}
