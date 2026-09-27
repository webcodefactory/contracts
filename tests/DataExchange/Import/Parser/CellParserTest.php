<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Import\Parser;

use Alumateria\Contracts\Shipping\ParcelSize;
use Alumateria\Contracts\Tests\DataExchange\Fixture\SampleStatus;
use Alumateria\Contracts\DataExchange\Import\Exception\CellException;
use Alumateria\Contracts\DataExchange\Import\Parser\CellParser;
use PHPUnit\Framework\TestCase;

final class CellParserTest extends TestCase
{
    private CellParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CellParser();
    }

    public function testBlankIsNullEverywhere(): void
    {
        self::assertNull($this->parser->bool(null));
        self::assertNull($this->parser->int(null));
        self::assertNull($this->parser->decimal(null));
        self::assertNull($this->parser->date(null));
        self::assertNull($this->parser->uuid(null));
        self::assertNull($this->parser->enum(null, SampleStatus::class, 'status'));
        self::assertNull($this->parser->parcelSize(null));
        self::assertSame([], $this->parser->list(null));
    }

    public function testBooleans(): void
    {
        foreach (['true', 'TAK', '1', 'yes'] as $raw) {
            self::assertTrue($this->parser->bool($raw), $raw);
        }
        foreach (['false', 'nie', '0', 'No'] as $raw) {
            self::assertFalse($this->parser->bool($raw), $raw);
        }
        $this->expectException(CellException::class);
        $this->parser->bool('może');
    }

    public function testNumbers(): void
    {
        self::assertSame(2000, $this->parser->int(' 2 000 '));
        self::assertSame(-5, $this->parser->int('-5'));
        self::assertSame('12.5', $this->parser->decimal('12,5'));
        self::assertSame('12.5', $this->parser->decimal('12.5'));
        self::assertSame('100', $this->parser->decimal('100'));
        self::assertSame('-0.25', $this->parser->decimal('-0,25'));

        try {
            $this->parser->int('12.5');
            self::fail('12.5 is not an int');
        } catch (CellException $e) {
            self::assertStringContainsString('liczbą całkowitą', $e->getMessage());
        }
        $this->expectException(CellException::class);
        $this->parser->decimal('1.23456');
    }

    public function testDates(): void
    {
        self::assertSame('2026-09-26', $this->parser->date('2026-09-26')?->format('Y-m-d'));
        self::assertSame('2026-09-26', $this->parser->date('26.09.2026')?->format('Y-m-d'));
        self::assertSame('2026-09-26', $this->parser->date('2026-09-26T13:00:00+02:00')?->format('Y-m-d'));
        $this->expectException(CellException::class);
        $this->parser->date('2026-13-45');
    }

    public function testUuidEnumParcelSizeAndList(): void
    {
        self::assertSame('a0000000-0000-4000-8000-000000000001', $this->parser->uuid('A0000000-0000-4000-8000-000000000001'));
        self::assertSame(SampleStatus::DRAFT, $this->parser->enum('Draft', SampleStatus::class, 'status'));
        self::assertSame(ParcelSize::XL, $this->parser->parcelSize('xl'));
        self::assertSame(['red', 'blue'], $this->parser->list(' red | blue | '));

        try {
            $this->parser->enum('archived', SampleStatus::class, 'status');
            self::fail('unknown enum value');
        } catch (CellException $e) {
            self::assertStringContainsString('draft, active, inactive', $e->getMessage());
        }
        $this->expectException(CellException::class);
        $this->parser->uuid('not-a-uuid');
    }
}
