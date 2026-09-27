<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

use Symfony\Component\Validator\Constraints as Assert;

final class ExportQueryDto
{
    public function __construct(
        #[Assert\Choice(callback: [ExportFormat::class, 'values'], message: 'Unsupported export format.')]
        public readonly string $format = ExportFormat::CSV->value,
    ) {
    }

    public function format(): ExportFormat
    {
        return ExportFormat::from($this->format);
    }
}
