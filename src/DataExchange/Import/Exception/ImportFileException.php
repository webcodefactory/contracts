<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Exception;

/**
 * The uploaded file cannot be turned into a table at all (unreadable,
 * malformed, no header). The message is shown to the admin as is.
 */
final class ImportFileException extends \RuntimeException
{
}
