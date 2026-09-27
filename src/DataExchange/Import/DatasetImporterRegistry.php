<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\DataExchange\DatasetInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class DatasetImporterRegistry
{
    /**
     * @param iterable<DatasetImporterInterface> $importers
     */
    public function __construct(
        #[TaggedIterator('alumateria.dataset_importer')]
        private readonly iterable $importers,
    ) {
    }

    public function for(DatasetInterface $dataset): DatasetImporterInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer->dataset() === $dataset) {
                return $importer;
            }
        }

        throw new \LogicException(sprintf('No importer registered for data set "%s".', $dataset->value));
    }
}
