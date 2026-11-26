<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Invoice;

/** How often a shop's invoice counter starts again from 1 */
enum NumberingPeriod: string
{
    case MONTH = 'month';
    case YEAR = 'year';
    case NEVER = 'never';

    /** The counter a date falls into: "2026-11", "2026" or one counter for good */
    public function keyFor(\DateTimeInterface $issuedOn): string
    {
        return match ($this) {
            self::MONTH => $issuedOn->format('Y-m'),
            self::YEAR => $issuedOn->format('Y'),
            self::NEVER => 'all',
        };
    }
}
