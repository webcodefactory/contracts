<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException;

/**
 * The parsed upload: a header and its rows. Column names are normalised
 * (trimmed, lower-cased, BOM stripped) so "SKU" and "sku" are the same.
 */
final class ImportTable implements \Countable
{
    public const MAX_ROWS = 50000;

    /**
     * @param list<string>    $columns
     * @param list<ImportRow> $rows
     */
    private function __construct(private readonly array $columns, private readonly array $rows)
    {
    }

    /**
     * @param list<string>    $columns already normalised
     * @param list<ImportRow> $rows
     */
    public static function create(array $columns, array $rows): self
    {
        if ($columns === []) {
            throw new ImportFileException('Plik nie zawiera nagłówka z nazwami kolumn.');
        }
        $duplicates = array_keys(array_filter(array_count_values($columns), static fn (int $n): bool => $n > 1));
        if ($duplicates !== []) {
            throw new ImportFileException(sprintf('Nagłówek zawiera powtórzone kolumny: %s.', implode(', ', $duplicates)));
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new ImportFileException(sprintf('Plik ma za dużo wierszy (%d); limit to %d na jeden import.', count($rows), self::MAX_ROWS));
        }

        return new self(array_values($columns), array_values($rows));
    }

    public static function normalizeColumn(string $name): string
    {
        return mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', $name)));
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    public function hasColumn(string $column): bool
    {
        return in_array($column, $this->columns, true);
    }

    /**
     * @param list<string> $columns
     */
    public function hasAnyColumn(array $columns): bool
    {
        return array_intersect($columns, $this->columns) !== [];
    }

    /**
     * @return list<ImportRow>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function count(): int
    {
        return count($this->rows);
    }
}
