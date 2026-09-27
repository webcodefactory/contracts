<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export\Writer;

use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use Alumateria\Contracts\DataExchange\Export\ExportRow;

/**
 * Semicolon-separated CSV with a UTF-8 BOM, the dialect Excel opens
 * correctly on a Polish locale. Booleans are written as true/false so
 * they stay distinguishable from numeric attribute values.
 */
final class CsvExportWriter implements ExportWriterInterface
{
    private const SEPARATOR = ';';
    private const BOM = "\xEF\xBB\xBF";

    public function format(): ExportFormat
    {
        return ExportFormat::CSV;
    }

    public function write($stream, ExportColumns $columns, iterable $rows): void
    {
        fwrite($stream, self::BOM);
        fputcsv($stream, $columns->keys(), self::SEPARATOR, '"', '');

        foreach ($rows as $row) {
            fputcsv($stream, array_map(self::cell(...), $row->values()), self::SEPARATOR, '"', '');
        }
    }

    private static function cell(string|int|float|bool|null $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}
