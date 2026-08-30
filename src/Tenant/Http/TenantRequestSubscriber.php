<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Http;

use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resolves the tenant from the X-Tenant-Id header. The header is trusted
 * because the gateway (Caddy) strips any client-supplied value and sets
 * its own based on the requested domain.
 *
 * Resolution is best-effort here (health checks carry no tenant);
 * enforcement is fail-closed at the point of use - TenantContext::get()
 * throws when the tenant was never resolved.
 */
final readonly class TenantRequestSubscriber implements EventSubscriberInterface
{
    public const HEADER = 'X-Tenant-Id';

    public function __construct(
        private TenantContext $tenantContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priority above routing/firewall so the tenant is available
        // to everything that runs later in the request cycle.
        return [KernelEvents::REQUEST => ['onKernelRequest', 64]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $header = $event->getRequest()->headers->get(self::HEADER);
        if ($header === null || $header === '') {
            return;
        }

        try {
            $this->tenantContext->set(TenantId::fromString($header));
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException('Invalid tenant header', $e);
        }
    }
}
