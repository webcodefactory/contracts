<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\DataExchange\DatasetInterface;

/**
 * Mutable collector the importers write into while they walk the rows;
 * build() freezes it into the report returned to the panel.
 */
final class ImportReportBuilder
{
    /** @var list<ImportIssue> */
    private array $issues = [];
    /** @var list<string> */
    private array $ignoredColumns = [];
    private bool $blocked = false;
    private int $rows = 0;
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    public function __construct(private readonly DatasetInterface $dataset, private readonly ImportMode $mode)
    {
    }

    public function rowError(ImportRow $row, ?string $column, string $message): void
    {
        $this->issues[] = ImportIssue::error($message, $row->number, $column);
    }

    public function rowWarning(ImportRow $row, ?string $column, string $message): void
    {
        $this->issues[] = ImportIssue::warning($message, $row->number, $column);
    }

    /**
     * A problem with the file itself: nothing gets imported until it is fixed.
     */
    public function fileError(string $message, ?string $column = null): void
    {
        $this->blocked = true;
        $this->issues[] = ImportIssue::error($message, null, $column);
    }

    public function fileWarning(string $message, ?string $column = null): void
    {
        $this->issues[] = ImportIssue::warning($message, null, $column);
    }

    public function ignoreColumn(string $column): void
    {
        if (!in_array($column, $this->ignoredColumns, true)) {
            $this->ignoredColumns[] = $column;
        }
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function created(): void
    {
        ++$this->rows;
        ++$this->created;
    }

    public function updated(): void
    {
        ++$this->rows;
        ++$this->updated;
    }

    public function skipped(): void
    {
        ++$this->rows;
        ++$this->skipped;
    }

    public function build(bool $applied): ImportReport
    {
        return new ImportReport(
            dataset: $this->dataset,
            mode: $this->mode,
            applied: $applied,
            blocked: $this->blocked,
            rows: $this->rows,
            created: $this->created,
            updated: $this->updated,
            skipped: $this->skipped,
            issues: $this->issues,
            ignoredColumns: $this->ignoredColumns,
        );
    }
}
