<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;


/**
 * What an importer needs beyond the table: the tenant, the report it
 * writes into, and a place to park side effects (domain events, cache
 * invalidation) that must only happen once the transaction committed.
 */
final class ImportContext
{
    /** @var list<\Closure> */
    private array $afterCommit = [];

    public function __construct(
        public readonly string $tenantId,
        public readonly ImportReportBuilder $report,
    ) {
    }

    public function afterCommit(\Closure $callback): void
    {
        $this->afterCommit[] = $callback;
    }

    public function runAfterCommit(): void
    {
        foreach ($this->afterCommit as $callback) {
            $callback();
        }
        $this->afterCommit = [];
    }
}
