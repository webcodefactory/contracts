<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant;

use Alumateria\Contracts\Tenant\Messenger\TenantContextMiddleware;
use Alumateria\Contracts\Tenant\Messenger\TenantStamp;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final class TenantContextMiddlewareTest extends TestCase
{
    private const TENANT = 'a0000000-0000-4000-8000-000000000001';

    /**
     * @param callable(Envelope): Envelope $onNext
     */
    private function stack(callable $onNext): StackInterface
    {
        $next = new class($onNext) implements MiddlewareInterface {
            /** @param callable(Envelope): Envelope $onNext */
            public function __construct(private $onNext)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                return ($this->onNext)($envelope);
            }
        };

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($next);

        return $stack;
    }

    public function testDispatchStampsEnvelopeWithCurrentTenant(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString(self::TENANT));

        $middleware = new TenantContextMiddleware($context);
        $result = $middleware->handle(
            new Envelope(new \stdClass()),
            $this->stack(fn (Envelope $e) => $e),
        );

        $stamp = $result->last(TenantStamp::class);
        $this->assertNotNull($stamp);
        $this->assertSame(self::TENANT, $stamp->tenantId);
    }

    public function testConsumeRestoresContextDuringHandlingAndClearsAfter(): void
    {
        $context = new TenantContext();
        $seenDuringHandling = null;

        $envelope = new Envelope(new \stdClass(), [
            new ReceivedStamp('async'),
            new TenantStamp(self::TENANT),
        ]);

        (new TenantContextMiddleware($context))->handle(
            $envelope,
            $this->stack(function (Envelope $e) use ($context, &$seenDuringHandling) {
                $seenDuringHandling = $context->get()->value;

                return $e;
            }),
        );

        $this->assertSame(self::TENANT, $seenDuringHandling);
        $this->assertFalse($context->has(), 'context must be cleared after handling');
    }

    public function testConsumeClearsContextEvenWhenHandlerThrows(): void
    {
        $context = new TenantContext();
        $envelope = new Envelope(new \stdClass(), [
            new ReceivedStamp('async'),
            new TenantStamp(self::TENANT),
        ]);

        try {
            (new TenantContextMiddleware($context))->handle(
                $envelope,
                $this->stack(fn () => throw new \RuntimeException('handler failed')),
            );
            $this->fail('exception expected');
        } catch (\RuntimeException) {
        }

        $this->assertFalse($context->has(), 'context must be cleared on failure too');
    }

    public function testConsumeWithoutStampLeavesContextUnset(): void
    {
        $context = new TenantContext();
        $envelope = new Envelope(new \stdClass(), [new ReceivedStamp('async')]);

        (new TenantContextMiddleware($context))->handle($envelope, $this->stack(fn (Envelope $e) => $e));

        $this->assertFalse($context->has());
    }
}
