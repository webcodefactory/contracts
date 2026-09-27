<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

/**
 * Ordered header of an export: every row of the document carries exactly
 * one cell per column, in this order. Keys are unique and non-empty.
 */
final class ExportColumns implements \Countable
{
    /** @var list<string> */
    private readonly array $keys;

    /**
     * @param list<string> $keys
     */
    private function __construct(array $keys)
    {
        if ($keys === []) {
            throw new \InvalidArgumentException('An export needs at least one column.');
        }
        foreach ($keys as $key) {
            if (!is_string($key) || trim($key) === '') {
                throw new \InvalidArgumentException('Export column keys must be non-empty strings.');
            }
        }
        if (count(array_unique($keys)) !== count($keys)) {
            throw new \InvalidArgumentException('Export column keys must be unique.');
        }

        $this->keys = array_values($keys);
    }

    /**
     * @param list<string> $keys
     */
    public static function fromList(array $keys): self
    {
        return new self($keys);
    }

    /**
     * @param list<string> $keys
     */
    public function with(array $keys): self
    {
        return new self([...$this->keys, ...$keys]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return $this->keys;
    }

    public function count(): int
    {
        return count($this->keys);
    }

    /**
     * @param array<string, string|int|float|bool|null> $cells values keyed by column; missing columns become null
     */
    public function row(array $cells): ExportRow
    {
        $unknown = array_diff(array_keys($cells), $this->keys);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf('Unknown export columns: %s.', implode(', ', $unknown)));
        }

        $ordered = [];
        foreach ($this->keys as $key) {
            $ordered[$key] = $cells[$key] ?? null;
        }

        return ExportRow::fromCells($ordered);
    }
}
