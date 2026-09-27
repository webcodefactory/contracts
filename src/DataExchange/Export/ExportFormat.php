<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

enum ExportFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';

    public function contentType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv; charset=UTF-8',
            self::JSON => 'application/json; charset=UTF-8',
        };
    }

    public function extension(): string
    {
        return $this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $format): string => $format->value, self::cases());
    }
}
