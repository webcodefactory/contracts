<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export\Writer;

use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use Alumateria\Contracts\DataExchange\Export\ExportRow;

/**
 * A JSON array of flat objects, one per row, keyed by the same column
 * names the CSV uses. Written row by row so the array can be arbitrarily
 * long; null cells are kept so every object has the full set of keys.
 */
final class JsonExportWriter implements ExportWriterInterface
{
    private const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    public function format(): ExportFormat
    {
        return ExportFormat::JSON;
    }

    public function write($stream, ExportColumns $columns, iterable $rows): void
    {
        fwrite($stream, '[');
        $first = true;
        foreach ($rows as $row) {
            fwrite($stream, ($first ? "\n" : ",\n") . json_encode($row->cells(), self::FLAGS));
            $first = false;
        }
        fwrite($stream, ($first ? '' : "\n") . ']');
    }
}
