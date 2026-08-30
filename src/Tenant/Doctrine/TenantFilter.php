<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Doctrine;

use Alumateria\Contracts\Tenant\Exception\TenantNotResolvedException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Fail-closed safety net for multi-tenancy: every ORM query on an entity
 * with a tenantId field is constrained to the current tenant. Querying
 * tenant-scoped data with no tenant resolved throws instead of silently
 * returning everything - the filter is the last line of defence behind
 * the explicitly tenant-scoped repositories, not a replacement for them.
 *
 * The parameter is kept in sync with TenantContext by
 * TenantFilterConfigurator, including mid-request changes
 * (webhook derive-then-set) and per-message worker context.
 */
final class TenantFilter extends SQLFilter
{
    public const NAME = 'tenant';
    public const PARAMETER = 'tenantId';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField('tenantId')) {
            return '';
        }

        if (!$this->hasParameter(self::PARAMETER)) {
            throw new TenantNotResolvedException();
        }

        return sprintf(
            '%s.%s = %s',
            $targetTableAlias,
            $targetEntity->getColumnName('tenantId'),
            $this->getParameter(self::PARAMETER),
        );
    }
}
