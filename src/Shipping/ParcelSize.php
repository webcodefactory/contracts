<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Shipping;

/**
 * Platform-wide parcel size classes ("gabaryt"). PIM classifies products,
 * offer-api relays the class to the storefront, cart-api prices shipping
 * methods per class - one vocabulary for all of them.
 *
 * Polish carriers each price by their own grid (InPost A/B/C, DHL XS-XXL,
 * ORLEN S/M/L, DPD by weight); a shop maps them onto these classes:
 *   S / M / L  - the locker grid shared by InPost, ORLEN Paczka, DHL and
 *                Pocztex (38 x 64 cm base, height 8 / 19 / 41 cm),
 *   XL         - courier parcel above the locker grid (up to 31.5 kg),
 *   XXL        - pallet / freight; lockers and pickup points never take it.
 */
enum ParcelSize: string
{
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = 'XXL';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $size): string => $size->value, self::cases());
    }

    /**
     * Accepted request values where blank means "not classified" (create)
     * or "clear" (update) - for Assert\Choice callbacks.
     *
     * @return list<string>
     */
    public static function valuesWithBlank(): array
    {
        return ['', ...self::values()];
    }

    /**
     * Strict parsing for trusted input: blank is null, anything else must
     * be a known code (case-insensitive).
     *
     * @throws \InvalidArgumentException on an unknown code
     */
    public static function fromNullableString(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $size = self::tryFrom(strtoupper(trim($value)));
        if ($size === null) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown parcel size "%s"; expected one of %s',
                $value,
                implode(', ', self::values()),
            ));
        }

        return $size;
    }

    /**
     * Lenient parsing for data relayed between services (event payloads,
     * scalar query results): an unknown or non-string value degrades to
     * "unclassified" instead of failing the consumer.
     */
    public static function fromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return self::tryFrom(strtoupper(trim($value)));
    }

    /**
     * The class a shipment of all given items needs. Unclassified items
     * (null) are ignored, so an all-unclassified set yields null.
     *
     * @param iterable<?self> $sizes
     */
    public static function largestOf(iterable $sizes): ?self
    {
        $largest = null;
        foreach ($sizes as $size) {
            if ($size !== null && ($largest === null || $size->isLargerThan($largest))) {
                $largest = $size;
            }
        }

        return $largest;
    }

    public function isLargerThan(self $other): bool
    {
        return $this->rank() > $other->rank();
    }

    public function label(): string
    {
        return match ($this) {
            self::S => 'Mała (gabaryt A)',
            self::M => 'Średnia (gabaryt B)',
            self::L => 'Duża (gabaryt C)',
            self::XL => 'Kurierska ponadgabarytowa',
            self::XXL => 'Paleta / spedycja',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::S => 'do 8 × 38 × 64 cm, do 25 kg',
            self::M => 'do 19 × 38 × 64 cm, do 25 kg',
            self::L => 'do 41 × 38 × 64 cm, do 25 kg',
            self::XL => 'ponad gabaryt C, do 31,5 kg – tylko kurier',
            self::XXL => 'przesyłka paletowa lub spedycyjna',
        };
    }

    private function rank(): int
    {
        return match ($this) {
            self::S => 1,
            self::M => 2,
            self::L => 3,
            self::XL => 4,
            self::XXL => 5,
        };
    }
}
