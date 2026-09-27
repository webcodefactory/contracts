<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

enum IssueSeverity: string
{
    case ERROR = 'error';
    case WARNING = 'warning';
}
