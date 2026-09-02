<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Domain;

use Alumateria\Contracts\Tenant\TenantId;

/**
 * Resolves the tenant of a shop domain (faza B: self-service domains).
 * Used by the request subscriber when the gateway did not force a tenant
 * header - i.e. for dynamically added domains served by the catch-all
 * gateway site.
 */
interface TenantDomainResolverInterface
{
    public function resolve(string $host): ?TenantId;
}
