<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Invoice;

/**
 * The pattern a shop numbers its invoices with, e.g. "FV/{RRRR}/{MM}/{NR:4}"
 * gives FV/2026/11/0001. Set in the store settings (settings-api), applied
 * when an invoice is issued (order-api) - one definition, so what the panel
 * accepts is exactly what the issuing side can number with.
 *
 * Tokens: {RRRR} year, {MM} month, {NR} the consecutive number, {NR:4} the
 * same padded with zeros to four digits. Everything else is literal text.
 *
 * The tokens also decide how often the counter restarts: a month in the
 * pattern means monthly numbering, a year alone yearly, neither - one
 * counter that never restarts. A month without a year is refused, because
 * the same number would come back twelve months later.
 */
final readonly class InvoiceNumberFormat implements \Stringable
{
    public const DEFAULT = 'FV/{RRRR}/{MM}/{NR:4}';
    /** Correcting invoices are a series of their own, with their own counter */
    public const DEFAULT_CORRECTION = 'FK/{RRRR}/{MM}/{NR:4}';
    public const MAX_LENGTH = 40;
    public const MAX_PADDING = 10;

    private const TOKEN = '/\{([^{}]*)\}/';
    private const LITERALS = '~^[\p{L}\p{N}/\-_. ]*$~u';

    private function __construct(
        public string $pattern,
        public NumberingPeriod $period,
    ) {
    }

    /**
     * @throws InvalidInvoiceNumberFormat
     */
    public static function fromString(string $pattern): self
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::EMPTY, 'Invoice number format is empty');
        }
        if (mb_strlen($pattern) > self::MAX_LENGTH) {
            throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::TOO_LONG, sprintf('Invoice number format is longer than %d characters', self::MAX_LENGTH));
        }

        preg_match_all(self::TOKEN, $pattern, $matches);
        $numbers = 0;
        $year = false;
        $month = false;
        foreach ($matches[1] as $token) {
            if ($token === 'RRRR') {
                $year = true;
            } elseif ($token === 'MM') {
                $month = true;
            } elseif (self::paddingOf($token) !== null) {
                ++$numbers;
            } else {
                throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::UNKNOWN_TOKEN, sprintf('Unknown token {%s} in the invoice number format', $token));
            }
        }

        $literals = (string) preg_replace(self::TOKEN, '', $pattern);
        if (preg_match(self::LITERALS, $literals) !== 1) {
            throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::FORBIDDEN_CHARACTER, 'Invoice number format may hold letters, digits, spaces and / - _ . besides its tokens');
        }
        if ($numbers === 0) {
            throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::NUMBER_MISSING, 'Invoice number format needs the {NR} token');
        }
        if ($numbers > 1) {
            throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::NUMBER_REPEATED, 'Invoice number format may hold the {NR} token once');
        }
        if ($month && !$year) {
            throw new InvalidInvoiceNumberFormat(InvoiceNumberFormatError::MONTH_WITHOUT_YEAR, 'Invoice number format with {MM} needs {RRRR} too');
        }

        return new self($pattern, match (true) {
            $month => NumberingPeriod::MONTH,
            $year => NumberingPeriod::YEAR,
            default => NumberingPeriod::NEVER,
        });
    }

    public static function default(): self
    {
        return self::fromString(self::DEFAULT);
    }

    public static function defaultForCorrections(): self
    {
        return self::fromString(self::DEFAULT_CORRECTION);
    }

    /**
     * The stored format of a shop; anything unusable falls back to the
     * default of the series ($fallback, the invoice default when not given).
     */
    public static function fromStored(mixed $pattern, ?self $fallback = null): self
    {
        if (!is_string($pattern) || trim($pattern) === '') {
            return $fallback ?? self::default();
        }
        try {
            return self::fromString($pattern);
        } catch (InvalidInvoiceNumberFormat) {
            return $fallback ?? self::default();
        }
    }

    /**
     * @throws \InvalidArgumentException when the sequence is not a positive number
     */
    public function number(int $sequence, \DateTimeInterface $issuedOn): string
    {
        if ($sequence < 1) {
            throw new \InvalidArgumentException('Invoice sequence starts at 1');
        }

        return (string) preg_replace_callback(
            self::TOKEN,
            static fn (array $m): string => match ($m[1]) {
                'RRRR' => $issuedOn->format('Y'),
                'MM' => $issuedOn->format('m'),
                default => str_pad((string) $sequence, (int) self::paddingOf($m[1]), '0', STR_PAD_LEFT),
            },
            $this->pattern,
        );
    }

    public function __toString(): string
    {
        return $this->pattern;
    }

    /** Width of a number token ("NR" => 1, "NR:4" => 4); null when the token is something else */
    private static function paddingOf(string $token): ?int
    {
        if ($token === 'NR') {
            return 1;
        }
        if (preg_match('/^NR:(\d{1,2})$/', $token, $m) !== 1) {
            return null;
        }
        $width = (int) $m[1];

        return $width >= 1 && $width <= self::MAX_PADDING ? $width : null;
    }
}
