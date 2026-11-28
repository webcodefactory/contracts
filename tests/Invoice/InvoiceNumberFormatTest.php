<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Invoice;

use Alumateria\Contracts\Invoice\InvalidInvoiceNumberFormat;
use Alumateria\Contracts\Invoice\InvoiceNumberFormat;
use Alumateria\Contracts\Invoice\InvoiceNumberFormatError;
use Alumateria\Contracts\Invoice\NumberingPeriod;
use PHPUnit\Framework\TestCase;

final class InvoiceNumberFormatTest extends TestCase
{
    public function testDefaultNumbersByMonthWithFourDigits(): void
    {
        $format = InvoiceNumberFormat::default();
        $day = new \DateTimeImmutable('2026-11-26');

        self::assertSame('FV/2026/11/0001', $format->number(1, $day));
        self::assertSame('FV/2026/11/0148', $format->number(148, $day));
        self::assertSame('FV/2026/11/12345', $format->number(12345, $day));
        self::assertSame(NumberingPeriod::MONTH, $format->period);
        self::assertSame('2026-11', $format->period->keyFor($day));
    }

    /** @return iterable<string, array{string, NumberingPeriod, string, string}> */
    public static function patterns(): iterable
    {
        yield 'year only' => ['TS/{NR:4}/{RRRR}', NumberingPeriod::YEAR, '2026', 'TS/0007/2026'];
        yield 'continuous' => ['F-{NR:6}', NumberingPeriod::NEVER, 'all', 'F-000007'];
        yield 'bare number' => ['{NR}', NumberingPeriod::NEVER, 'all', '7'];
        yield 'polish letters and spaces' => ['Sprzedaż {RRRR}.{MM} nr {NR}', NumberingPeriod::MONTH, '2026-03', 'Sprzedaż 2026.03 nr 7'];
        yield 'trimmed' => ['  FV/{RRRR}/{NR}  ', NumberingPeriod::YEAR, '2026', 'FV/2026/7'];
    }

    /** @dataProvider patterns */
    public function testPeriodFollowsTheTokens(string $pattern, NumberingPeriod $period, string $key, string $number): void
    {
        $format = InvoiceNumberFormat::fromString($pattern);
        $day = new \DateTimeImmutable('2026-03-09');

        self::assertSame($period, $format->period);
        self::assertSame($key, $format->period->keyFor($day));
        self::assertSame($number, $format->number(7, $day));
        self::assertSame(trim($pattern), (string) $format);
    }

    /** @return iterable<string, array{string, InvoiceNumberFormatError}> */
    public static function refused(): iterable
    {
        yield 'empty' => ['   ', InvoiceNumberFormatError::EMPTY];
        yield 'too long' => [str_repeat('A', 38) . '{NR}', InvoiceNumberFormatError::TOO_LONG];
        yield 'no number' => ['FV/{RRRR}/{MM}', InvoiceNumberFormatError::NUMBER_MISSING];
        yield 'number twice' => ['FV/{NR}/{NR:3}', InvoiceNumberFormatError::NUMBER_REPEATED];
        yield 'month without year' => ['FV/{MM}/{NR}', InvoiceNumberFormatError::MONTH_WITHOUT_YEAR];
        yield 'unknown token' => ['FV/{YYYY}/{NR}', InvoiceNumberFormatError::UNKNOWN_TOKEN];
        yield 'padding out of range' => ['FV/{NR:11}', InvoiceNumberFormatError::UNKNOWN_TOKEN];
        yield 'zero padding' => ['FV/{NR:0}', InvoiceNumberFormatError::UNKNOWN_TOKEN];
        yield 'stray brace' => ['FV/{NR}}', InvoiceNumberFormatError::FORBIDDEN_CHARACTER];
        yield 'markup' => ['<b>{NR}</b>', InvoiceNumberFormatError::FORBIDDEN_CHARACTER];
        yield 'percent' => ['FV%{NR}', InvoiceNumberFormatError::FORBIDDEN_CHARACTER];
    }

    /** @dataProvider refused */
    public function testRefusesWhatCannotNumberInvoices(string $pattern, InvoiceNumberFormatError $error): void
    {
        try {
            InvoiceNumberFormat::fromString($pattern);
            self::fail('Accepted: ' . $pattern);
        } catch (InvalidInvoiceNumberFormat $e) {
            self::assertSame($error, $e->error);
        }
    }

    public function testStoredGarbageFallsBackToTheDefault(): void
    {
        self::assertSame(InvoiceNumberFormat::DEFAULT, (string) InvoiceNumberFormat::fromStored(null));
        self::assertSame(InvoiceNumberFormat::DEFAULT, (string) InvoiceNumberFormat::fromStored(''));
        self::assertSame(InvoiceNumberFormat::DEFAULT, (string) InvoiceNumberFormat::fromStored('FV/{MM}'));
        self::assertSame('F/{NR}', (string) InvoiceNumberFormat::fromStored('F/{NR}'));
    }

    public function testCorrectionsAreASeriesWithTheirOwnDefault(): void
    {
        $corrections = InvoiceNumberFormat::defaultForCorrections();

        self::assertSame('FK/2026/11/0001', $corrections->number(1, new \DateTimeImmutable('2026-11-28')));
        self::assertSame(InvoiceNumberFormat::DEFAULT_CORRECTION, (string) InvoiceNumberFormat::fromStored(null, $corrections));
        self::assertSame(InvoiceNumberFormat::DEFAULT_CORRECTION, (string) InvoiceNumberFormat::fromStored('K/{MM}', $corrections));
        self::assertSame('K/{NR}', (string) InvoiceNumberFormat::fromStored('K/{NR}', $corrections));
    }

    public function testSequenceStartsAtOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InvoiceNumberFormat::default()->number(0, new \DateTimeImmutable());
    }
}
