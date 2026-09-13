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

    /** @var list<TenantChangeListenerInterface> */
    private array $listeners = [];

    public function subscribe(TenantChangeListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
        // A late subscriber sees a RESOLVED tenant immediately. An unresolved
        // context has nothing to sync (the Doctrine filter without a parameter
        // already fails closed) and must not touch listeners: the context is
        // often constructed from inside Doctrine's own lazy initialisation
        // (EntityManager -> cache pool logger -> TenantProcessor -> here), and
        // TenantFilterConfigurator calling getFilters() on that half-built
        // EntityManager ghost is a fatal "$config must not be accessed before
        // initialization" - it killed every prod image build.
        if ($this->tenantId !== null) {
            $listener->onTenantChanged($this->tenantId);
        }
    }

    public function set(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->notify();
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
        $this->notify();
    }

    private function notify(): void
    {
        foreach ($this->listeners as $listener) {
            $listener->onTenantChanged($this->tenantId);
        }
    }
}
