<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;

use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\DataExchange\Import\Parser\ImportParserRegistry;
use Alumateria\Contracts\DataExchange\DatasetInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Runs an import inside one database transaction. A validate run does
 * everything a real run does - lookups, entity changes, the flush - and
 * rolls back at the end, so its report is exactly what apply would do.
 * Domain events are dispatched only after a successful commit.
 */
final class ImportService
{
    public function __construct(
        private readonly ImportParserRegistry $parsers,
        private readonly DatasetImporterRegistry $importers,
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * @throws \Alumateria\Contracts\DataExchange\Import\Exception\ImportFileException when the file cannot be parsed
     */
    public function import(DatasetInterface $dataset, UploadedFile $file, ImportMode $mode): ImportReport
    {
        $table = $this->parsers->parse($file);
        $context = new ImportContext($this->tenantContext->get()->value, new ImportReportBuilder($dataset, $mode));

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        $applied = false;
        try {
            $this->importers->for($dataset)->import($table, $context);
            $this->entityManager->flush();

            if ($mode === ImportMode::APPLY && !$context->report->isBlocked()) {
                $connection->commit();
                $applied = true;
                $context->runAfterCommit();
            } else {
                $connection->rollBack();
            }
        } catch (UniqueConstraintViolationException $e) {
            $connection->rollBack();
            $context->report->fileError('Baza danych odrzuciła zapis z powodu konfliktu unikalności (np. uuid lub kod użyty już w innym sklepie). '.$e->getMessage());
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $e;
        } finally {
            $this->entityManager->clear();
        }

        return $context->report->build($applied);
    }
}
