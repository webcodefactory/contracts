<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Carries the tenant of a dispatched message through the transport so
 * the consuming worker can restore the TenantContext before handling.
 */
final readonly class TenantStamp implements StampInterface
{
    public function __construct(
        public string $tenantId,
    ) {
    }
}
