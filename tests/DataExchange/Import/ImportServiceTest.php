<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Import;

use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use Alumateria\Contracts\DataExchange\Import\ImportMode;
use Alumateria\Contracts\DataExchange\Import\DatasetImporterInterface;
use Alumateria\Contracts\DataExchange\Import\DatasetImporterRegistry;
use Alumateria\Contracts\DataExchange\Import\ImportContext;
use Alumateria\Contracts\DataExchange\Import\Parser\CsvImportParser;
use Alumateria\Contracts\DataExchange\Import\Parser\ImportParserRegistry;
use Alumateria\Contracts\DataExchange\Import\ImportService;
use Alumateria\Contracts\DataExchange\Import\ImportTable;
use Alumateria\Contracts\Tests\DataExchange\Fixture\SampleDataset;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportServiceTest extends TestCase
{
    private const TENANT = 'b0000000-0000-4000-8000-000000000002';

    private ?string $file = null;
    private bool $afterCommitRan = false;
    private Connection&MockObject $connection;
    private EntityManagerInterface&MockObject $entityManager;

    protected function tearDown(): void
    {
        if ($this->file !== null) {
            @unlink($this->file);
        }
    }

    private function upload(): UploadedFile
    {
        $this->file = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($this->file, "sku;nazwa\nA;Produkt\n");

        return new UploadedFile($this->file, 'produkty.csv', null, null, true);
    }

    private function service(bool $blocked = false, ?\Throwable $flushFailure = null): ImportService
    {
        $importer = new class($blocked, function (): void { $this->afterCommitRan = true; }) implements DatasetImporterInterface {
            public function __construct(private readonly bool $blocked, private readonly \Closure $onCommit)
            {
            }

            public function dataset(): SampleDataset
            {
                return SampleDataset::PRODUCTS;
            }

            public function import(ImportTable $table, ImportContext $context): void
            {
                if ($this->blocked) {
                    $context->report->fileError('zablokowane');

                    return;
                }
                $context->report->created();
                $context->afterCommit($this->onCommit);
            }
        };

        $this->connection = $this->createMock(Connection::class);
        $this->connection->method('isTransactionActive')->willReturn(true);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        if ($flushFailure !== null) {
            $this->entityManager->method('flush')->willThrowException($flushFailure);
        }
        $this->entityManager->expects(self::once())->method('clear');

        $tenant = new TenantContext();
        $tenant->set(TenantId::fromString(self::TENANT));

        return new ImportService(new ImportParserRegistry([new CsvImportParser()]), new DatasetImporterRegistry([$importer]), $this->entityManager, $tenant);
    }

    public function testValidateRunsEverythingAndRollsBack(): void
    {
        $service = $this->service();
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');
        $this->entityManager->expects(self::once())->method('flush');

        $report = $service->import(SampleDataset::PRODUCTS, $this->upload(), ImportMode::VALIDATE);

        self::assertFalse($report->applied);
        self::assertSame(1, $report->created);
        self::assertFalse($this->afterCommitRan);
    }

    public function testApplyCommitsAndRunsDeferredSideEffects(): void
    {
        $service = $this->service();
        $this->connection->expects(self::once())->method('commit');
        $this->connection->expects(self::never())->method('rollBack');

        $report = $service->import(SampleDataset::PRODUCTS, $this->upload(), ImportMode::APPLY);

        self::assertTrue($report->applied);
        self::assertTrue($this->afterCommitRan);
    }

    public function testBlockedFileIsNeverCommitted(): void
    {
        $service = $this->service(blocked: true);
        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');

        $report = $service->import(SampleDataset::PRODUCTS, $this->upload(), ImportMode::APPLY);

        self::assertFalse($report->applied);
        self::assertTrue($report->blocked);
        self::assertFalse($this->afterCommitRan);
    }

    public function testUniqueConstraintViolationBecomesAFileError(): void
    {
        $failure = new UniqueConstraintViolationException(PdoDriverException::new(new \PDOException('Duplicate entry')), null);
        $service = $this->service(flushFailure: $failure);
        $this->connection->expects(self::once())->method('rollBack');

        $report = $service->import(SampleDataset::PRODUCTS, $this->upload(), ImportMode::APPLY);

        self::assertFalse($report->applied);
        self::assertTrue($report->blocked);
        self::assertStringContainsString('konfliktu unikalności', $report->issues[0]->message);
        self::assertFalse($this->afterCommitRan);
    }

    public function testUnexpectedFailureRollsBackAndRethrows(): void
    {
        $service = $this->service(flushFailure: new \RuntimeException('db down'));
        $this->connection->expects(self::once())->method('rollBack');

        $this->expectException(\RuntimeException::class);
        $service->import(SampleDataset::PRODUCTS, $this->upload(), ImportMode::APPLY);
    }
}
