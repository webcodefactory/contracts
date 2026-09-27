<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Parser;

use Alumateria\Contracts\DataExchange\Import\ImportFileFormat;
use Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException;
use Alumateria\Contracts\DataExchange\Import\ImportRow;
use Alumateria\Contracts\DataExchange\Import\ImportTable;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A JSON array of flat objects, as the export writes it. Scalars become
 * the strings the CSV path would have seen (true → "true", 12.5 → "12.5"),
 * so both formats go through the same cell parsing. Row numbers are
 * 1-based positions in the array.
 */
final class JsonImportParser implements ImportParserInterface
{
    public function format(): ImportFileFormat
    {
        return ImportFileFormat::JSON;
    }

    public function parse(UploadedFile $file): ImportTable
    {
        try {
            $data = json_decode((string) file_get_contents($file->getPathname()), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ImportFileException(sprintf('Plik nie jest poprawnym JSON: %s.', $e->getMessage()));
        }

        if (!is_array($data) || !array_is_list($data)) {
            throw new ImportFileException('Plik JSON musi zawierać tablicę obiektów (jeden obiekt = jeden wiersz).');
        }

        $columns = [];
        $rows = [];
        foreach ($data as $index => $item) {
            $number = $index + 1;
            if (!is_array($item) || array_is_list($item)) {
                throw new ImportFileException(sprintf('Element %d nie jest obiektem z polami.', $number));
            }

            $cells = [];
            foreach ($item as $key => $value) {
                $column = ImportTable::normalizeColumn((string) $key);
                if ($column === '') {
                    continue;
                }
                if (!in_array($column, $columns, true)) {
                    $columns[] = $column;
                }
                $cells[$column] = self::cell($value, $number, $column);
            }
            $row = ImportRow::fromCells($number, $cells);
            if (!$row->isBlank()) {
                $rows[] = $row;
            }
        }

        return ImportTable::create($columns, $rows);
    }

    private static function cell(mixed $value, int $row, string $column): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) json_encode($value),
            is_string($value) => $value,
            default => throw new ImportFileException(sprintf('Wiersz %d, pole „%s”: wartości zagnieżdżone nie są obsługiwane.', $row, $column)),
        };
    }
}
