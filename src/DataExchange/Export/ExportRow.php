<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

/**
 * One record of an export: scalar cells keyed by column, already in the
 * document's column order (built through ExportColumns::row()).
 */
final class ExportRow
{
    /**
     * @param array<string, string|int|float|bool|null> $cells
     */
    private function __construct(private readonly array $cells)
    {
    }

    /**
     * @param array<string, string|int|float|bool|null> $cells
     */
    public static function fromCells(array $cells): self
    {
        foreach ($cells as $key => $cell) {
            if ($cell !== null && !is_scalar($cell)) {
                throw new \InvalidArgumentException(sprintf('Export cell "%s" must be scalar or null.', $key));
            }
        }

        return new self($cells);
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    public function cells(): array
    {
        return $this->cells;
    }

    /**
     * @return list<string|int|float|bool|null>
     */
    public function values(): array
    {
        return array_values($this->cells);
    }
}
