<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Export\Writer;

use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use Alumateria\Contracts\DataExchange\Export\Writer\JsonExportWriter;
use PHPUnit\Framework\TestCase;

final class JsonExportWriterTest extends TestCase
{
    public function testWritesArrayOfFlatObjectsKeepingTypesAndNulls(): void
    {
        $columns = ExportColumns::fromList(['sku', 'nazwa', 'aktywny', 'ilosc', 'opis']);
        $rows = [
            $columns->row(['sku' => 'A-1', 'nazwa' => 'Zażółć/gęślą', 'aktywny' => true, 'ilosc' => 12]),
            $columns->row(['sku' => 'B-2', 'aktywny' => false, 'ilosc' => 1.0, 'opis' => 'x']),
        ];

        $stream = fopen('php://memory', 'w+');
        $writer = new JsonExportWriter();
        $writer->write($stream, $columns, $rows);
        rewind($stream);
        $json = stream_get_contents($stream);

        self::assertSame(ExportFormat::JSON, $writer->format());
        self::assertSame(
            "[\n"
            .'{"sku":"A-1","nazwa":"Zażółć/gęślą","aktywny":true,"ilosc":12,"opis":null},'."\n"
            .'{"sku":"B-2","nazwa":null,"aktywny":false,"ilosc":1.0,"opis":"x"}'."\n"
            .']',
            $json,
        );
        self::assertCount(2, json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    public function testEmptyDataSetIsAnEmptyArray(): void
    {
        $stream = fopen('php://memory', 'w+');
        (new JsonExportWriter())->write($stream, ExportColumns::fromList(['a']), []);
        rewind($stream);

        self::assertSame('[]', stream_get_contents($stream));
    }
}
