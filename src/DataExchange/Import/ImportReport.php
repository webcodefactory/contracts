<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\DataExchange\DatasetInterface;

/**
 * Outcome of one import run. "blocked" means the file as a whole was
 * refused (unknown attribute column, missing identifier column...) and no
 * row was processed; "applied" is true only after a committed apply run.
 */
final class ImportReport implements \JsonSerializable
{
    public const MAX_ISSUES_IN_RESPONSE = 500;

    /**
     * @param list<ImportIssue> $issues
     * @param list<string>      $ignoredColumns
     */
    public function __construct(
        public readonly DatasetInterface $dataset,
        public readonly ImportMode $mode,
        public readonly bool $applied,
        public readonly bool $blocked,
        public readonly int $rows,
        public readonly int $created,
        public readonly int $updated,
        public readonly int $skipped,
        public readonly array $issues,
        public readonly array $ignoredColumns,
    ) {
    }

    public function hasErrors(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity->value === 'error') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'dataset' => $this->dataset->value,
            'mode' => $this->mode->value,
            'applied' => $this->applied,
            'blocked' => $this->blocked,
            'rows' => $this->rows,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'issues' => array_slice($this->issues, 0, self::MAX_ISSUES_IN_RESPONSE),
            'issuesTotal' => count($this->issues),
            'ignoredColumns' => $this->ignoredColumns,
        ];
    }
}
