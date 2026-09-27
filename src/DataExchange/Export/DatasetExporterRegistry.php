<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Export;

use Alumateria\Contracts\DataExchange\DatasetInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class DatasetExporterRegistry
{
    /**
     * @param iterable<DatasetExporterInterface> $exporters
     */
    public function __construct(
        #[TaggedIterator('alumateria.dataset_exporter')]
        private readonly iterable $exporters,
    ) {
    }

    public function for(DatasetInterface $dataset): DatasetExporterInterface
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->dataset() === $dataset) {
                return $exporter;
            }
        }

        throw new \LogicException(sprintf('No exporter registered for data set "%s".', $dataset->value));
    }
}
