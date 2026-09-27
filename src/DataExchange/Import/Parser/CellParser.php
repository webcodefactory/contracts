<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Parser;

use Alumateria\Contracts\Shipping\ParcelSize;
use Alumateria\Contracts\DataExchange\Import\Exception\CellException;
use Symfony\Component\Uid\Uuid;

/**
 * Turns raw cells into typed values. Every method accepts null (blank
 * cell) and returns null for it; anything else that does not parse is a
 * CellException with a message meant for the admin.
 */
final class CellParser
{
    public const LIST_SEPARATOR = '|';

    private const TRUE = ['true', '1', 'tak', 'yes', 'y', 't'];
    private const FALSE = ['false', '0', 'nie', 'no', 'n', 'f'];

    public function bool(?string $raw): ?bool
    {
        if ($raw === null) {
            return null;
        }
        $value = mb_strtolower(trim($raw));
        if (in_array($value, self::TRUE, true)) {
            return true;
        }
        if (in_array($value, self::FALSE, true)) {
            return false;
        }

        throw new CellException(sprintf('„%s” nie jest wartością logiczną (oczekiwano true/false lub tak/nie).', $raw));
    }

    public function int(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        $value = str_replace(' ', '', trim($raw));
        if (!preg_match('/^-?\d{1,18}$/', $value)) {
            throw new CellException(sprintf('„%s” nie jest liczbą całkowitą.', $raw));
        }

        return (int) $value;
    }

    /**
     * Decimal kept as a string so precision is not lost; "12,5" and "12.5"
     * are both accepted, the stored form uses a dot.
     */
    public function decimal(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $value = str_replace([' ', ','], ['', '.'], trim($raw));
        if (!preg_match('/^-?\d{1,12}(\.\d{1,4})?$/', $value)) {
            throw new CellException(sprintf('„%s” nie jest liczbą dziesiętną (maks. 4 miejsca po przecinku).', $raw));
        }

        return $value;
    }

    public function date(?string $raw): ?\DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }
        $value = trim($raw);
        foreach (['Y-m-d', 'd.m.Y', 'd-m-Y', 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->setTime(0, 0);
            }
        }

        throw new CellException(sprintf('„%s” nie jest datą (oczekiwano RRRR-MM-DD lub DD.MM.RRRR).', $raw));
    }

    public function uuid(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $value = strtolower(trim($raw));
        if (!Uuid::isValid($value)) {
            throw new CellException(sprintf('„%s” nie jest poprawnym UUID.', $raw));
        }

        return $value;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T|null
     */
    public function enum(?string $raw, string $enumClass, string $what): ?\BackedEnum
    {
        if ($raw === null) {
            return null;
        }
        $value = $enumClass::tryFrom(mb_strtolower(trim($raw)));
        if ($value === null) {
            $allowed = array_map(static fn (\BackedEnum $case): string => (string) $case->value, $enumClass::cases());

            throw new CellException(sprintf('„%s” nie jest poprawną wartością pola %s (dozwolone: %s).', $raw, $what, implode(', ', $allowed)));
        }

        return $value;
    }

    public function parcelSize(?string $raw): ?ParcelSize
    {
        if ($raw === null) {
            return null;
        }
        $size = ParcelSize::tryFrom(strtoupper(trim($raw)));
        if ($size === null) {
            throw new CellException(sprintf('„%s” nie jest gabarytem (dozwolone: %s).', $raw, implode(', ', ParcelSize::values())));
        }

        return $size;
    }

    /**
     * "a|b|c" → ["a", "b", "c"], blanks dropped.
     *
     * @return list<string>
     */
    public function list(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(self::LIST_SEPARATOR, $raw)),
            static fn (string $item): bool => $item !== '',
        ));
    }
}
