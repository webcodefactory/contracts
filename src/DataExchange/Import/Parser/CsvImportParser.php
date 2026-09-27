<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Parser;

use Alumateria\Contracts\DataExchange\Import\ImportFileFormat;
use Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException;
use Alumateria\Contracts\DataExchange\Import\ImportRow;
use Alumateria\Contracts\DataExchange\Import\ImportTable;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Reads the CSV dialect the export writes (UTF-8, optional BOM, ";")
 * and the comma/tab variants spreadsheets produce. The delimiter is
 * picked from the header line; quoted cells may span lines. Row numbers
 * are file line numbers, so they match what the admin sees in Excel.
 */
final class CsvImportParser implements ImportParserInterface
{
    private const DELIMITERS = [';', ',', "\t"];

    public function format(): ImportFileFormat
    {
        return ImportFileFormat::CSV;
    }

    public function parse(UploadedFile $file): ImportTable
    {
        $path = $file->getPathname();
        if (!is_readable($path)) {
            throw new ImportFileException('Nie udało się odczytać przesłanego pliku.');
        }

        $delimiter = self::detectDelimiter((string) file_get_contents($path, false, null, 0, 8192));

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new ImportFileException('Nie udało się odczytać przesłanego pliku.');
        }

        $columns = null;
        $rows = [];
        $line = 0;
        try {
            while (($fields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $startLine = $line + 1;
                // A quoted cell may span lines; keep counting physical lines so
                // reported row numbers match the spreadsheet the admin looks at
                $line += 1 + array_sum(array_map(static fn ($f): int => substr_count((string) $f, "\n"), $fields));

                if ($fields === [null]) {
                    continue;
                }
                if ($columns === null) {
                    $columns = self::header($fields);
                    continue;
                }

                $cells = [];
                foreach ($columns as $index => $column) {
                    if ($column === null) {
                        continue;
                    }
                    $cell = $fields[$index] ?? null;
                    $cells[$column] = $cell === null ? null : (string) $cell;
                }
                $row = ImportRow::fromCells($startLine, $cells);
                if (!$row->isBlank()) {
                    $rows[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        if ($columns === null) {
            throw new ImportFileException('Plik CSV jest pusty.');
        }

        return ImportTable::create(array_values(array_filter($columns, static fn (?string $c): bool => $c !== null)), $rows);
    }

    /**
     * @param list<string|null> $fields
     *
     * @return array<int, string|null> column name per position; null for a blank header cell
     */
    private static function header(array $fields): array
    {
        $columns = [];
        foreach ($fields as $index => $field) {
            $name = ImportTable::normalizeColumn((string) $field);
            $columns[$index] = $name === '' ? null : $name;
        }

        return $columns;
    }

    private static function detectDelimiter(string $head): string
    {
        $firstLine = strtok($head, "\r\n") ?: '';
        $best = ';';
        $bestCount = -1;
        foreach (self::DELIMITERS as $delimiter) {
            $count = substr_count($firstLine, $delimiter);
            if ($count > $bestCount) {
                $best = $delimiter;
                $bestCount = $count;
            }
        }

        return $best;
    }
}
