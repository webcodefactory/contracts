<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\DataExchange\DatasetInterface;
use Alumateria\Contracts\DataExchange\Export\Writer\ExportWriterRegistry;

final class ExportService
{
    public function __construct(
        private readonly DatasetExporterRegistry $exporters,
        private readonly ExportWriterRegistry $writers,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function prepare(DatasetInterface $dataset, ExportFormat $format): ExportDocument
    {
        return ExportDocument::for($dataset, $format, new \DateTimeImmutable());
    }

    /**
     * Streams the whole data set of the current tenant into $stream.
     *
     * @param resource $stream
     */
    public function write(ExportDocument $document, $stream): void
    {
        $tenantId = $this->tenantContext->get()->value;
        $exporter = $this->exporters->for($document->dataset);
        $columns = $exporter->columns($tenantId);

        $this->writers->for($document->format)->write($stream, $columns, $exporter->rows($tenantId, $columns));
    }
}
