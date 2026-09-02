<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant\Doctrine;

use Alumateria\Contracts\Tenant\Doctrine\TenantFilter;
use Alumateria\Contracts\Tenant\Exception\TenantNotResolvedException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

final class TenantFilterTest extends TestCase
{
    private const TENANT = 'a0000000-0000-4000-8000-000000000001';

    private function createFilter(): TenantFilter
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quote')->willReturnCallback(fn (string $v) => "'" . $v . "'");

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        return new TenantFilter($em);
    }

    private function metadata(bool $hasTenantField, bool $exempt = false): ClassMetadata
    {
        $entity = $exempt
            ? new #[\Alumateria\Contracts\Tenant\Doctrine\WithoutTenantFilter] class {}
            : new class {};

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->with('tenantId')->willReturn($hasTenantField);
        $metadata->method('getColumnName')->with('tenantId')->willReturn('tenant_id');
        $metadata->method('getReflectionClass')->willReturn(new \ReflectionClass($entity));

        return $metadata;
    }

    public function testExemptRegistryEntityIsNotFiltered(): void
    {
        $filter = $this->createFilter();

        $this->assertSame('', $filter->addFilterConstraint($this->metadata(true, exempt: true), 't0'));
    }

    public function testIgnoresEntitiesWithoutTenantField(): void
    {
        $filter = $this->createFilter();

        $this->assertSame('', $filter->addFilterConstraint($this->metadata(false), 't0'));
    }

    public function testConstrainsTenantEntitiesToCurrentTenant(): void
    {
        $filter = $this->createFilter();
        $filter->setParameter(TenantFilter::PARAMETER, self::TENANT);

        $this->assertSame(
            "t0.tenant_id = '" . self::TENANT . "'",
            $filter->addFilterConstraint($this->metadata(true), 't0'),
        );
    }

    public function testFailsClosedWhenTenantNotResolved(): void
    {
        $filter = $this->createFilter();

        $this->expectException(TenantNotResolvedException::class);

        $filter->addFilterConstraint($this->metadata(true), 't0');
    }
}
