<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant;

use Alumateria\Contracts\Tenant\Exception\TenantNotResolvedException;

/**
 * Per-process holder of the current tenant. Set once at the system edge
 * (HTTP subscriber, Messenger middleware, CLI command) and read everywhere
 * else. Reading an unset context throws - never defaults.
 */
final class TenantContext
{
    private ?TenantId $tenantId = null;

    public function set(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function get(): TenantId
    {
        return $this->tenantId ?? throw new TenantNotResolvedException();
    }

    public function has(): bool
    {
        return $this->tenantId !== null;
    }

    /**
     * Long-running processes (Messenger workers) must clear the context
     * between units of work so one message can never leak its tenant
     * into the next.
     */
    public function clear(): void
    {
        $this->tenantId = null;
    }
}
