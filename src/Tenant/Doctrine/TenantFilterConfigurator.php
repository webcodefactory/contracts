<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Doctrine;

use Alumateria\Contracts\Tenant\TenantChangeListenerInterface;
use Alumateria\Contracts\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Keeps the Doctrine TenantFilter parameter in sync with TenantContext.
 * Subscribed to the context by TenantBundle, so ANY context change -
 * HTTP resolution, Messenger stamp, CLI, derive-then-set - immediately
 * re-scopes all subsequent ORM queries.
 *
 * A cleared context removes nothing: the filter simply has no parameter
 * again, and the next tenant-scoped query fails closed.
 */
final readonly class TenantFilterConfigurator implements TenantChangeListenerInterface
{
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    public function onTenantChanged(?TenantId $tenantId): void
    {
        foreach ($this->registry->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            $filters = $manager->getFilters();
            if (!$filters->has(TenantFilter::NAME)) {
                continue;
            }

            $filter = $filters->isEnabled(TenantFilter::NAME)
                ? $filters->getFilter(TenantFilter::NAME)
                : $filters->enable(TenantFilter::NAME);

            if ($tenantId === null) {
                // SQLFilter has no "unset parameter" API - recreate the
                // filter in its pristine (parameterless) state so the next
                // tenant-scoped query fails closed instead of using a
                // stale tenant.
                $filters->disable(TenantFilter::NAME);
                $filters->enable(TenantFilter::NAME);
                continue;
            }

            $filter->setParameter(TenantFilter::PARAMETER, $tenantId->value);
        }
    }
}
