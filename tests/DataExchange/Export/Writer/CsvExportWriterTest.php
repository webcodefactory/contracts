<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Export\Writer;

use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use Alumateria\Contracts\DataExchange\Export\Writer\CsvExportWriter;
use PHPUnit\Framework\TestCase;

final class CsvExportWriterTest extends TestCase
{
    public function testWritesBomHeaderAndSemicolonSeparatedRows(): void
    {
        $columns = ExportColumns::fromList(['sku', 'nazwa', 'aktywny', 'ilosc', 'opis']);
        $rows = (static function () use ($columns) {
            yield $columns->row(['sku' => 'A-1', 'nazwa' => 'Zażółć; "gęślą"', 'aktywny' => true, 'ilosc' => 12, 'opis' => null]);
            yield $columns->row(['sku' => 'B-2', 'nazwa' => "wiele\nlinii", 'aktywny' => false, 'ilosc' => 0.5]);
        })();

        $stream = fopen('php://memory', 'w+');
        $writer = new CsvExportWriter();
        $writer->write($stream, $columns, $rows);
        rewind($stream);
        $csv = stream_get_contents($stream);

        self::assertSame(ExportFormat::CSV, $writer->format());
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
        self::assertSame(
            "sku;nazwa;aktywny;ilosc;opis\n"
            ."A-1;\"Zażółć; \"\"gęślą\"\"\";true;12;\n"
            ."B-2;\"wiele\nlinii\";false;0.5;\n",
            substr($csv, 3),
        );
    }

    public function testEmptyDataSetStillHasAHeader(): void
    {
        $stream = fopen('php://memory', 'w+');
        (new CsvExportWriter())->write($stream, ExportColumns::fromList(['a', 'b']), []);
        rewind($stream);

        self::assertSame("\xEF\xBB\xBFa;b\n", stream_get_contents($stream));
    }
}
