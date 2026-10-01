<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

enum ImportFileFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';

    /**
     * The extension the browser sent decides; without a telling extension
     * the first significant byte does (a JSON export starts with "[").
     * Any other extension (xlsx, pdf, ...) is refused: parsing a spreadsheet
     * or a binary as CSV would only produce a misleading report.
     *
     * @throws ImportFileException
     */
    public static function detect(UploadedFile $file): self
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'json') {
            return self::JSON;
        }
        if (in_array($extension, ['csv', 'txt'], true)) {
            return self::CSV;
        }
        if ($extension !== '') {
            throw new ImportFileException(sprintf('Nieobsługiwany format pliku „.%s”; wyślij plik CSV lub JSON.', $extension));
        }

        $head = (string) file_get_contents($file->getPathname(), false, null, 0, 64);
        $head = ltrim($head, "\xEF\xBB\xBF \t\r\n");

        return str_starts_with($head, '[') || str_starts_with($head, '{') ? self::JSON : self::CSV;
    }
}
