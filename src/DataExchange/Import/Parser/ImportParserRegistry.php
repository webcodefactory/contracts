<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Parser;

use Alumateria\Contracts\DataExchange\Import\ImportFileFormat;
use Alumateria\Contracts\DataExchange\Import\ImportTable;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportParserRegistry
{
    /**
     * @param iterable<ImportParserInterface> $parsers
     */
    public function __construct(
        #[TaggedIterator('alumateria.import_parser')]
        private readonly iterable $parsers,
    ) {
    }

    public function parse(UploadedFile $file): ImportTable
    {
        return $this->for(ImportFileFormat::detect($file))->parse($file);
    }

    public function for(ImportFileFormat $format): ImportParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->format() === $format) {
                return $parser;
            }
        }

        throw new \LogicException(sprintf('No import parser registered for format "%s".', $format->value));
    }
}
