<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\DataExchange\DatasetInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Applies one data set's rows to the tenant's entities without flushing;
 * the service owns the transaction. Column semantics shared by all
 * importers: a column that is absent leaves the field untouched, a
 * present but blank cell clears it.
 */
#[AutoconfigureTag('alumateria.dataset_importer')]
interface DatasetImporterInterface
{
    public function dataset(): DatasetInterface;

    public function import(ImportTable $table, ImportContext $context): void;
}
