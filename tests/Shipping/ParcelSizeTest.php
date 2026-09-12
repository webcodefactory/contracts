<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Shipping;

use Alumateria\Contracts\Shipping\ParcelSize;
use PHPUnit\Framework\TestCase;

final class ParcelSizeTest extends TestCase
{
    public function testSizesAreOrderedFromSmallestToLargest(): void
    {
        self::assertTrue(ParcelSize::M->isLargerThan(ParcelSize::S));
        self::assertTrue(ParcelSize::XXL->isLargerThan(ParcelSize::XL));
        self::assertFalse(ParcelSize::S->isLargerThan(ParcelSize::S));
        self::assertFalse(ParcelSize::L->isLargerThan(ParcelSize::XL));
    }

    public function testLargestOfIgnoresUnclassifiedItems(): void
    {
        self::assertSame(ParcelSize::L, ParcelSize::largestOf([ParcelSize::S, null, ParcelSize::L, ParcelSize::M]));
        self::assertNull(ParcelSize::largestOf([null, null]));
        self::assertNull(ParcelSize::largestOf([]));
    }

    public function testStrictParsingAcceptsBlankAsNullAndIsCaseInsensitive(): void
    {
        self::assertNull(ParcelSize::fromNullableString(null));
        self::assertNull(ParcelSize::fromNullableString('  '));
        self::assertSame(ParcelSize::XL, ParcelSize::fromNullableString('xl'));
    }

    public function testStrictParsingRejectsUnknownCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ParcelSize::fromNullableString('GIGANT');
    }

    public function testLenientParsingDegradesToUnclassified(): void
    {
        self::assertSame(ParcelSize::M, ParcelSize::fromMixed('m'));
        self::assertSame(ParcelSize::L, ParcelSize::fromMixed(ParcelSize::L));
        self::assertNull(ParcelSize::fromMixed(null));
        self::assertNull(ParcelSize::fromMixed(''));
        self::assertNull(ParcelSize::fromMixed('GIGANT'));
        self::assertNull(ParcelSize::fromMixed(42));
    }

    public function testRequestVocabularies(): void
    {
        self::assertSame(['S', 'M', 'L', 'XL', 'XXL'], ParcelSize::values());
        self::assertSame(['', 'S', 'M', 'L', 'XL', 'XXL'], ParcelSize::valuesWithBlank());
    }

    public function testEveryCaseHasLabelAndDescription(): void
    {
        foreach (ParcelSize::cases() as $size) {
            self::assertNotSame('', $size->label());
            self::assertNotSame('', $size->description());
        }
    }
}
