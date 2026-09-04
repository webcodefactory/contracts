<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Http;

use Alumateria\Contracts\Tenant\TenantAwareUserInterface;
use Alumateria\Contracts\Tenant\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Enforces tenant memberships of authenticated users: a ROLE_ADMIN user may
 * only operate on a tenant listed in their tenant_ids claim (request tenant
 * comes from the X-Tenant-Id header sent by the admin panel's shop switcher
 * and validated here - never trusted on its own). A ROLE_CUSTOMER token is
 * bound to the single tenant it was issued for - using it against another
 * shop's domain fails even though the account row itself is tenant-scoped.
 *
 * Service tokens (ROLE_SERVICE, no memberships) are exempt: services act
 * cross-tenant with the explicitly propagated caller tenant.
 */
final readonly class TenantAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private TenantContext $tenantContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // After the firewall (priority 8) so the token is available
        return [KernelEvents::REQUEST => ['onKernelRequest', 4]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $roles = $token->getRoleNames();
        if (!in_array('ROLE_ADMIN', $roles, true) && !in_array('ROLE_CUSTOMER', $roles, true)) {
            return;
        }

        $user = $token->getUser();
        $allowed = $user instanceof TenantAwareUserInterface ? $user->getTenantIds() : [];

        // Fail-closed: a user without memberships (or without a resolved
        // request tenant) gets 403, never implicit full access
        if (!$this->tenantContext->has()
            || !in_array($this->tenantContext->get()->value, $allowed, true)
        ) {
            throw new AccessDeniedHttpException('User has no access to this tenant');
        }
    }
}
