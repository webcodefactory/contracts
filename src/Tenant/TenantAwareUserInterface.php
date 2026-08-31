<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant;

/**
 * Authenticated principal with tenant memberships (admin panel users).
 * Implemented by the JWT user of the security package; the tenant_ids
 * claim is issued by Keycloak from the user's memberships.
 */
interface TenantAwareUserInterface
{
    /**
     * @return list<string> tenant uuids the user may operate on
     */
    public function getTenantIds(): array;
}
