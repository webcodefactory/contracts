<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant;

/**
 * Notified whenever the TenantContext is set or cleared. Allows
 * infrastructure (e.g. the Doctrine tenant filter) to stay in sync with
 * the context no matter WHERE it was changed - HTTP subscriber, Messenger
 * middleware, CLI command or a derive-then-set webhook flow.
 */
interface TenantChangeListenerInterface
{
    public function onTenantChanged(?TenantId $tenantId): void;
}
