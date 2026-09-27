<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import\Parser;

use Alumateria\Contracts\DataExchange\Import\ImportFileFormat;
use Alumateria\Contracts\DataExchange\Import\ImportTable;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AutoconfigureTag('alumateria.import_parser')]
interface ImportParserInterface
{
    public function format(): ImportFileFormat;

    /**
     * @throws \Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException when the file is not a usable table
     */
    public function parse(UploadedFile $file): ImportTable;
}
