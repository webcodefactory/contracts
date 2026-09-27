<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Export;

use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use Alumateria\Contracts\Tests\DataExchange\Fixture\SampleDataset;
use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\DatasetExporterInterface;
use Alumateria\Contracts\DataExchange\Export\DatasetExporterRegistry;
use Alumateria\Contracts\DataExchange\Export\ExportService;
use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use Alumateria\Contracts\DataExchange\Export\Writer\CsvExportWriter;
use Alumateria\Contracts\DataExchange\Export\Writer\ExportWriterRegistry;
use Alumateria\Contracts\DataExchange\Export\Writer\JsonExportWriter;
use PHPUnit\Framework\TestCase;

final class ExportServiceTest extends TestCase
{
    private const TENANT = 'b0000000-0000-4000-8000-000000000002';

    public function testWritesTheCurrentTenantsDataSetWithTheRequestedWriter(): void
    {
        $columns = ExportColumns::fromList(['kod', 'nazwa']);
        $exporter = $this->createMock(DatasetExporterInterface::class);
        $exporter->method('dataset')->willReturn(SampleDataset::CATEGORIES);
        $exporter->expects(self::once())->method('columns')->with(self::TENANT)->willReturn($columns);
        $exporter->expects(self::once())->method('rows')->with(self::TENANT, $columns)
            ->willReturn([$columns->row(['kod' => 'c1', 'nazwa' => 'Chemia'])]);

        $context = new TenantContext();
        $context->set(TenantId::fromString(self::TENANT));
        $service = new ExportService(
            new DatasetExporterRegistry([$exporter]),
            new ExportWriterRegistry([new CsvExportWriter(), new JsonExportWriter()]),
            $context,
        );

        $document = $service->prepare(SampleDataset::CATEGORIES, ExportFormat::JSON);
        $stream = fopen('php://memory', 'w+');
        $service->write($document, $stream);
        rewind($stream);

        self::assertSame(ExportFormat::JSON, $document->format);
        self::assertStringEndsWith('.json', $document->filename());
        self::assertSame("[\n".'{"kod":"c1","nazwa":"Chemia"}'."\n]", stream_get_contents($stream));
    }

    public function testUnknownDataSetOrFormatIsAProgrammingError(): void
    {
        $this->expectException(\LogicException::class);

        (new DatasetExporterRegistry([]))->for(SampleDataset::PRODUCTS);
    }

    public function testMissingWriterIsAProgrammingError(): void
    {
        $this->expectException(\LogicException::class);

        (new ExportWriterRegistry([new CsvExportWriter()]))->for(ExportFormat::JSON);
    }

    public function testExportWithoutTenantFailsClosed(): void
    {
        $service = new ExportService(new DatasetExporterRegistry([]), new ExportWriterRegistry([]), new TenantContext());
        $document = $service->prepare(SampleDataset::PRODUCTS, ExportFormat::CSV);

        $this->expectException(\Throwable::class);

        $service->write($document, fopen('php://memory', 'w+'));
    }
}
