<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Exception;

/**
 * One cell of one row is unusable; the message becomes a row issue.
 */
final class CellException extends \InvalidArgumentException
{
}
