<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Http;

use Alumateria\Contracts\Tenant\TenantContext;

/**
 * Tenant propagation for service-to-service HTTP calls: internal requests
 * must carry the CALLER's tenant, because the gateway only forces the
 * X-Tenant-Id header on public (non /api/internal/*) paths. Fail-closed:
 * building headers without a resolved tenant throws.
 */
final readonly class TenantHeaders
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function asArray(): array
    {
        return [TenantRequestSubscriber::HEADER => $this->tenantContext->get()->value];
    }
}
