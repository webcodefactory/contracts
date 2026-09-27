<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Symfony\Component\Validator\Constraints as Assert;

final class ImportQueryDto
{
    public function __construct(
        #[Assert\Choice(callback: [ImportMode::class, 'values'], message: 'Unsupported import mode.')]
        public readonly string $mode = ImportMode::VALIDATE->value,
    ) {
    }

    public function mode(): ImportMode
    {
        return ImportMode::from($this->mode);
    }
}
