<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Http;

use Alumateria\Contracts\Tenant\Domain\TenantDomainResolverInterface;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resolves the tenant from the X-Tenant-Id header, falling back to
 * Host-based resolution for dynamically added shop domains (faza B).
 *
 * The header is trusted because the gateway either sets it itself
 * (static shop domains, internal/admin flows) or strips any
 * client-supplied value (catch-all dynamic site) - so an absent header
 * safely means "resolve by Host".
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
        private ?TenantDomainResolverInterface $domainResolver = null,
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
        if ($header !== null && $header !== '') {
            try {
                $this->tenantContext->set(TenantId::fromString($header));
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid tenant header', $e);
            }

            return;
        }

        $tenantId = $this->domainResolver?->resolve($event->getRequest()->getHost());
        if ($tenantId !== null) {
            $this->tenantContext->set($tenantId);
        }
    }
}
