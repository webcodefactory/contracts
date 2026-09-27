<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Export;

use Alumateria\Contracts\Tests\DataExchange\Fixture\SampleDataset;
use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\ExportDocument;
use PHPUnit\Framework\TestCase;

final class ExportDocumentTest extends TestCase
{
    public function testFilenameCarriesPolishStemDateAndExtension(): void
    {
        $at = new \DateTimeImmutable('2026-09-26 13:45:00');

        self::assertSame('produkty_2026-09-26.csv', ExportDocument::for(SampleDataset::PRODUCTS, ExportFormat::CSV, $at)->filename());
        self::assertSame('kategorie_2026-09-26.json', ExportDocument::for(SampleDataset::CATEGORIES, ExportFormat::JSON, $at)->filename());
        self::assertSame('atrybuty_2026-09-26.csv', ExportDocument::for(SampleDataset::ATTRIBUTES, ExportFormat::CSV, $at)->filename());
    }

    public function testContentTypeFollowsFormat(): void
    {
        $at = new \DateTimeImmutable();

        self::assertSame('text/csv; charset=UTF-8', ExportDocument::for(SampleDataset::PRODUCTS, ExportFormat::CSV, $at)->contentType());
        self::assertSame('application/json; charset=UTF-8', ExportDocument::for(SampleDataset::PRODUCTS, ExportFormat::JSON, $at)->contentType());
    }

    public function testFormatValuesBackTheQueryChoice(): void
    {
        self::assertSame(['csv', 'json'], ExportFormat::values());
    }
}
