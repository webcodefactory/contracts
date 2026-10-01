<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Import;

use Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException;
use Alumateria\Contracts\DataExchange\Import\ImportFileFormat;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportFileFormatTest extends TestCase
{
    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function testExtensionDecidesForCsvAndJson(): void
    {
        self::assertSame(ImportFileFormat::CSV, ImportFileFormat::detect($this->upload('produkty.CSV', 'sku;name')));
        self::assertSame(ImportFileFormat::CSV, ImportFileFormat::detect($this->upload('produkty.txt', 'sku;name')));
        self::assertSame(ImportFileFormat::JSON, ImportFileFormat::detect($this->upload('produkty.json', 'sku;name')));
    }

    public function testWithoutExtensionTheContentDecides(): void
    {
        self::assertSame(ImportFileFormat::JSON, ImportFileFormat::detect($this->upload('export', "\xEF\xBB\xBF  [{\"sku\":\"A\"}]")));
        self::assertSame(ImportFileFormat::CSV, ImportFileFormat::detect($this->upload('export', 'sku;name')));
    }

    public function testASpreadsheetIsRefusedInsteadOfBeingParsedAsCsv(): void
    {
        $this->expectException(ImportFileException::class);
        $this->expectExceptionMessage('.xlsx');
        ImportFileFormat::detect($this->upload('produkty.xlsx', "PK\x03\x04" . str_repeat('x', 100)));
    }
}
