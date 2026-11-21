<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Shipping;

use Alumateria\Contracts\Shipping\DispatchTime;
use PHPUnit\Framework\TestCase;

final class DispatchTimeTest extends TestCase
{
    public function testReadsAsTheEndOfWysylkaWCiagu(): void
    {
        self::assertSame('24 h', DispatchTime::ofDays(1, 1)->label());
        self::assertSame('3 dni roboczych', DispatchTime::ofDays(3, 3)->label());
        self::assertSame('1–2 dni roboczych', DispatchTime::ofDays(1, 2)->label());
        self::assertSame('5–7 dni roboczych', DispatchTime::ofDays(5, 7)->label());
    }

    public function testRefusesRangesThatMakeNoSense(): void
    {
        foreach ([[0, 1], [3, 2], [-1, -1], [1, DispatchTime::MAX_DAYS + 1]] as [$min, $max]) {
            try {
                DispatchTime::ofDays($min, $max);
                self::fail(sprintf('%d-%d should be refused', $min, $max));
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testNotStatedIsNullAndHalfARangeIsAMistake(): void
    {
        self::assertNull(DispatchTime::ofNullableDays(null, null));
        self::assertTrue(DispatchTime::ofDays(2, 4)->equals(DispatchTime::ofNullableDays(2, 4)));

        $this->expectException(\InvalidArgumentException::class);
        DispatchTime::ofNullableDays(2, null);
    }

    public function testTheOrderWaitsForItsSlowestItem(): void
    {
        $own = DispatchTime::ofDays(1, 1);
        $supplier = DispatchTime::ofDays(5, 7);

        self::assertSame($supplier, DispatchTime::longest($own, null, $supplier));
        self::assertSame($own, DispatchTime::longest(null, $own));
        self::assertNull(DispatchTime::longest(null, null));
        self::assertNull(DispatchTime::longest());
        // the same upper end: the later start is the longer wait
        self::assertTrue(DispatchTime::ofDays(3, 5)->isLongerThan(DispatchTime::ofDays(1, 5)));
        self::assertFalse($own->isLongerThan($own));
    }

    public function testTravelsBetweenServicesAsJson(): void
    {
        $time = DispatchTime::ofDays(5, 7);

        self::assertSame(['minDays' => 5, 'maxDays' => 7, 'label' => '5–7 dni roboczych'], $time->jsonSerialize());
        self::assertTrue($time->equals(DispatchTime::fromArray(json_decode((string) json_encode($time), true))));
        self::assertNull(DispatchTime::fromArray(null));
        self::assertNull(DispatchTime::fromArray(['minDays' => 7, 'maxDays' => 5]));
        self::assertNull(DispatchTime::fromArray(['minDays' => 'soon', 'maxDays' => 5]));
    }
}
