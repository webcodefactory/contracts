<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Import\Parser;

use Alumateria\Contracts\DataExchange\Import\ImportFileFormat;
use Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException;
use Alumateria\Contracts\DataExchange\Import\Parser\CsvImportParser;
use Alumateria\Contracts\DataExchange\Import\Parser\ImportParserRegistry;
use Alumateria\Contracts\DataExchange\Import\Parser\JsonImportParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileParsersTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function upload(string $content, string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    public function testCsvWithBomSemicolonsQuotesAndBlankLines(): void
    {
        $csv = "\xEF\xBB\xBFUUID;Nazwa;opis;\n"
            ."a1;Kwas;\"wiele\nlinii; ze średnikiem\";extra\n"
            ."\n"
            ."a2;;\n"
            .";;\n";
        $table = (new CsvImportParser())->parse($this->upload($csv, 'produkty.csv'));

        self::assertSame(['uuid', 'nazwa', 'opis'], $table->columns());
        self::assertCount(2, $table);
        [$first, $second] = $table->rows();
        self::assertSame(2, $first->number);
        self::assertSame('a1', $first->get('uuid'));
        self::assertSame("wiele\nlinii; ze średnikiem", $first->get('opis'));
        self::assertSame(5, $second->number, 'row numbers are file line numbers');
        self::assertNull($second->get('nazwa'));
        self::assertTrue($table->hasColumn('opis'));
        self::assertFalse($table->hasColumn('extra'));
    }

    public function testCsvCommaDialectIsDetectedFromTheHeader(): void
    {
        $table = (new CsvImportParser())->parse($this->upload("sku,nazwa\nA-1,\"Zażółć, gęślą\"\n", 'x.csv'));

        self::assertSame(['sku', 'nazwa'], $table->columns());
        self::assertSame('Zażółć, gęślą', $table->rows()[0]->get('nazwa'));
    }

    public function testCsvRejectsDuplicateHeaderAndEmptyFile(): void
    {
        try {
            (new CsvImportParser())->parse($this->upload("sku;sku\n1;2\n", 'x.csv'));
            self::fail('duplicate header');
        } catch (ImportFileException $e) {
            self::assertStringContainsString('powtórzone kolumny: sku', $e->getMessage());
        }
        $this->expectException(ImportFileException::class);
        (new CsvImportParser())->parse($this->upload('', 'x.csv'));
    }

    public function testJsonObjectsBecomeStringCells(): void
    {
        $json = '[{"SKU":"A-1","aktywna":true,"ilosc":12,"waga":1.5,"opis":null},{"sku":"B-2","nowa":"x"}]';
        $table = (new JsonImportParser())->parse($this->upload($json, 'x.json'));

        self::assertSame(['sku', 'aktywna', 'ilosc', 'waga', 'opis', 'nowa'], $table->columns());
        $first = $table->rows()[0];
        self::assertSame(1, $first->number);
        self::assertSame('true', $first->get('aktywna'));
        self::assertSame('12', $first->get('ilosc'));
        self::assertSame('1.5', $first->get('waga'));
        self::assertNull($first->get('opis'));
        self::assertSame('x', $table->rows()[1]->get('nowa'));
    }

    public function testJsonRejectsNonListAndNestedValues(): void
    {
        try {
            (new JsonImportParser())->parse($this->upload('{"sku":"A"}', 'x.json'));
            self::fail('not a list');
        } catch (ImportFileException $e) {
            self::assertStringContainsString('tablicę obiektów', $e->getMessage());
        }
        try {
            (new JsonImportParser())->parse($this->upload('[{"sku":"A","tags":["x"]}]', 'x.json'));
            self::fail('nested');
        } catch (ImportFileException $e) {
            self::assertStringContainsString('zagnieżdżone', $e->getMessage());
        }
        $this->expectException(ImportFileException::class);
        (new JsonImportParser())->parse($this->upload('{not json', 'x.json'));
    }

    public function testFormatDetectionAndRegistry(): void
    {
        self::assertSame(ImportFileFormat::JSON, ImportFileFormat::detect($this->upload('[]', 'a.json')));
        self::assertSame(ImportFileFormat::CSV, ImportFileFormat::detect($this->upload('[]', 'a.csv')));
        self::assertSame(ImportFileFormat::JSON, ImportFileFormat::detect($this->upload("\xEF\xBB\xBF  [{}]", 'blob')));
        self::assertSame(ImportFileFormat::CSV, ImportFileFormat::detect($this->upload('sku;nazwa', 'blob')));

        $registry = new ImportParserRegistry([new CsvImportParser(), new JsonImportParser()]);
        self::assertSame(['sku'], $registry->parse($this->upload('[{"sku":"A"}]', 'x.json'))->columns());
        self::assertSame(['sku'], $registry->parse($this->upload("sku\nA\n", 'x.csv'))->columns());
    }
}
