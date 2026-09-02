<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Domain;

/**
 * Raw (non-namespaced) Redis key of the domain->tenant mapping, shared
 * by ALL services: settings-api writes it, every service's resolver
 * reads it. Deliberately bypasses Symfony cache pools - their per-app
 * namespaces would make the key invisible across services.
 */
final class TenantDomainCacheKey
{
    private function __construct()
    {
    }

    public static function for(string $host): string
    {
        return 'alumateria:tenant_domain:' . strtolower($host);
    }
}
