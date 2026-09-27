<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

use Alumateria\Contracts\DataExchange\DatasetInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Reads one data set of a tenant and maps it to export rows. Column sets
 * may depend on the tenant (products get one column per attribute code),
 * which is why the header is resolved per export, not statically.
 */
#[AutoconfigureTag('alumateria.dataset_exporter')]
interface DatasetExporterInterface
{
    public function dataset(): DatasetInterface;

    public function columns(string $tenantId): ExportColumns;

    /**
     * @return iterable<ExportRow>
     */
    public function rows(string $tenantId, ExportColumns $columns): iterable;
}
