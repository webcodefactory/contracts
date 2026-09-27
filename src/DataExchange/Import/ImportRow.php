<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

/**
 * One record of the uploaded file. Cells are raw strings keyed by the
 * normalised column name; get() trims and turns blanks into null, which
 * is the import's "clear this field" marker.
 */
final class ImportRow
{
    /**
     * @param array<string, string|null> $cells
     */
    private function __construct(public readonly int $number, private readonly array $cells)
    {
    }

    /**
     * @param array<string, string|null> $cells
     */
    public static function fromCells(int $number, array $cells): self
    {
        foreach ($cells as $column => $cell) {
            if ($cell !== null && !is_string($cell)) {
                throw new \InvalidArgumentException(sprintf('Cell "%s" of row %d must be a string or null.', $column, $number));
            }
        }

        return new self($number, $cells);
    }

    public function get(string $column): ?string
    {
        $value = $this->cells[$column] ?? null;
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public function isBlank(): bool
    {
        foreach ($this->cells as $cell) {
            if ($cell !== null && trim($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string|null>
     */
    public function cells(): array
    {
        return $this->cells;
    }
}
