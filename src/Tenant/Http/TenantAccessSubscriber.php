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
 * Enforces admin tenant memberships: an authenticated ROLE_ADMIN user may
 * only operate on a tenant listed in their tenant_ids claim. The request
 * tenant comes from the X-Tenant-Id header (sent by the admin panel's
 * shop switcher and validated here - never trusted on its own).
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
        if ($token === null || !in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return;
        }

        $user = $token->getUser();
        $allowed = $user instanceof TenantAwareUserInterface ? $user->getTenantIds() : [];

        // Fail-closed: an admin without memberships (or without a resolved
        // request tenant) gets 403, never implicit full access
        if (!$this->tenantContext->has()
            || !in_array($this->tenantContext->get()->value, $allowed, true)
        ) {
            throw new AccessDeniedHttpException('Admin has no access to this tenant');
        }
    }
}
