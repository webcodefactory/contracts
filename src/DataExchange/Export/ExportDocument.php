<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

use Alumateria\Contracts\DataExchange\DatasetInterface;

/**
 * What the admin is downloading: which data set, in which format, stamped
 * with the generation date that ends up in the file name.
 */
final class ExportDocument
{
    private function __construct(
        public readonly DatasetInterface $dataset,
        public readonly ExportFormat $format,
        public readonly \DateTimeImmutable $generatedAt,
    ) {
    }

    public static function for(DatasetInterface $dataset, ExportFormat $format, \DateTimeImmutable $generatedAt): self
    {
        return new self($dataset, $format, $generatedAt);
    }

    public function filename(): string
    {
        return sprintf('%s_%s.%s', $this->dataset->filenameStem(), $this->generatedAt->format('Y-m-d'), $this->format->extension());
    }

    public function contentType(): string
    {
        return $this->format->contentType();
    }
}
