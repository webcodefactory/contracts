<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Doctrine;

/**
 * Marks an entity that carries a tenantId column but is NOT tenant-scoped
 * data - platform registry entities (e.g. the tenant domain registry)
 * that must be readable without a resolved tenant. The TenantFilter
 * skips such entities; use deliberately and rarely.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class WithoutTenantFilter
{
}
