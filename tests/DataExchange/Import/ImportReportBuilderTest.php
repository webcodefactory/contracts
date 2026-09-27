<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Import;

use Alumateria\Contracts\DataExchange\Import\ImportMode;
use Alumateria\Contracts\DataExchange\Import\ImportReportBuilder;
use Alumateria\Contracts\DataExchange\Import\ImportRow;
use Alumateria\Contracts\Tests\DataExchange\Fixture\SampleDataset;
use PHPUnit\Framework\TestCase;

final class ImportReportBuilderTest extends TestCase
{
    public function testCountsIssuesAndJsonShape(): void
    {
        $builder = new ImportReportBuilder(SampleDataset::PRODUCTS, ImportMode::VALIDATE);
        $row = ImportRow::fromCells(7, ['sku' => 'A']);
        $builder->created();
        $builder->updated();
        $builder->rowError($row, 'status', 'zły status');
        $builder->skipped();
        $builder->rowWarning($row, 'sku', 'uwaga');
        $builder->fileWarning('kolumna pominięta', 'xyz');
        $builder->ignoreColumn('xyz');
        $builder->ignoreColumn('xyz');

        self::assertFalse($builder->isBlocked());
        $report = $builder->build(false);
        $json = $report->jsonSerialize();

        self::assertSame('products', $json['dataset']);
        self::assertSame('validate', $json['mode']);
        self::assertFalse($json['applied']);
        self::assertFalse($json['blocked']);
        self::assertSame([3, 1, 1, 1], [$json['rows'], $json['created'], $json['updated'], $json['skipped']]);
        self::assertSame(3, $json['issuesTotal']);
        self::assertSame(['xyz'], $json['ignoredColumns']);
        self::assertSame(['severity' => 'error', 'message' => 'zły status', 'row' => 7, 'column' => 'status'], $json['issues'][0]->jsonSerialize());
        self::assertTrue($report->hasErrors());
    }

    public function testFileErrorBlocks(): void
    {
        $builder = new ImportReportBuilder(SampleDataset::CATEGORIES, ImportMode::APPLY);
        $builder->fileError('brak kolumny');

        self::assertTrue($builder->isBlocked());
        self::assertTrue($builder->build(false)->blocked);
        self::assertNull($builder->build(false)->issues[0]->row);
    }
}
