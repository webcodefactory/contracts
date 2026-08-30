<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Messenger;

use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Propagates the tenant across the message bus:
 *  - dispatch: stamps outgoing messages with the current tenant,
 *  - consume: restores TenantContext from the stamp for the duration of
 *    handling and clears it afterwards, so a worker process can never
 *    leak one message's tenant into the next.
 */
final readonly class TenantContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($envelope->last(ReceivedStamp::class) !== null) {
            return $this->handleReceived($envelope, $stack);
        }

        if ($this->tenantContext->has() && $envelope->last(TenantStamp::class) === null) {
            $envelope = $envelope->with(new TenantStamp($this->tenantContext->get()->value));
        }

        return $stack->next()->handle($envelope, $stack);
    }

    private function handleReceived(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(TenantStamp::class);
        if ($stamp === null) {
            // No tenant on the message: leave the context unset. Handlers of
            // tenant-scoped data will fail closed via TenantContext::get().
            return $stack->next()->handle($envelope, $stack);
        }

        $this->tenantContext->set(TenantId::fromString($stamp->tenantId));

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
