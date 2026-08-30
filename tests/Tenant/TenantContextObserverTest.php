<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant;

use Alumateria\Contracts\Tenant\TenantChangeListenerInterface;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

final class TenantContextObserverTest extends TestCase
{
    /** @return array{0: TenantChangeListenerInterface, 1: list<?string>} */
    private function recordingListener(): array
    {
        $seen = [];
        $listener = new class($seen) implements TenantChangeListenerInterface {
            /** @param list<?string> $seen */
            public function __construct(private array &$seen)
            {
            }

            public function onTenantChanged(?TenantId $tenantId): void
            {
                $this->seen[] = $tenantId?->value;
            }
        };

        return [$listener, &$seen];
    }

    public function testListenerSeesSetAndClear(): void
    {
        $context = new TenantContext();
        [$listener, ] = $pair = $this->recordingListener();
        $seen = &$pair[1];

        $context->subscribe($listener);
        $context->set(TenantId::fromString('a0000000-0000-4000-8000-000000000001'));
        $context->clear();

        $this->assertSame(
            [null, 'a0000000-0000-4000-8000-000000000001', null],
            $seen,
            'subscribe syncs current state, then every set/clear notifies',
        );
    }

    public function testLateSubscriberIsSyncedImmediately(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString('a0000000-0000-4000-8000-000000000001'));

        [$listener, ] = $pair = $this->recordingListener();
        $seen = &$pair[1];
        $context->subscribe($listener);

        $this->assertSame(['a0000000-0000-4000-8000-000000000001'], $seen);
    }
}
