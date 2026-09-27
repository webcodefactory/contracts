<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

/**
 * validate = full dry run (every row checked, nothing kept), apply = commit.
 */
enum ImportMode: string
{
    case VALIDATE = 'validate';
    case APPLY = 'apply';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $mode): string => $mode->value, self::cases());
    }
}
