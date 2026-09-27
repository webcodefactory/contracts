<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange;

/**
 * A named set of records a service can export to a file and import back.
 * Implemented by each service's backed enum of data sets; the key is the
 * URL segment and JSON value, the stem names downloaded files.
 */
interface DatasetInterface
{
    public function key(): string;

    public function filenameStem(): string;
}
