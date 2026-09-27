<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export\Writer;

use Alumateria\Contracts\DataExchange\Export\ExportFormat;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class ExportWriterRegistry
{
    /**
     * @param iterable<ExportWriterInterface> $writers
     */
    public function __construct(
        #[TaggedIterator('alumateria.export_writer')]
        private readonly iterable $writers,
    ) {
    }

    public function for(ExportFormat $format): ExportWriterInterface
    {
        foreach ($this->writers as $writer) {
            if ($writer->format() === $format) {
                return $writer;
            }
        }

        throw new \LogicException(sprintf('No export writer registered for format "%s".', $format->value));
    }
}
