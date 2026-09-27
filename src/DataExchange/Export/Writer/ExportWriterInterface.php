<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export\Writer;

use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use Alumateria\Contracts\DataExchange\Export\ExportRow;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Serialises rows into one file format. Writers stream: a row is written
 * as soon as the exporter yields it, nothing is buffered in memory.
 */
#[AutoconfigureTag('alumateria.export_writer')]
interface ExportWriterInterface
{
    public function format(): ExportFormat;

    /**
     * @param resource               $stream
     * @param iterable<ExportRow>    $rows
     */
    public function write($stream, ExportColumns $columns, iterable $rows): void;
}
