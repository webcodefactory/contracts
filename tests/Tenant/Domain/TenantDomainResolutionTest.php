<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant\Domain;

use Alumateria\Contracts\Tenant\Domain\PredisTenantDomainResolver;
use Alumateria\Contracts\Tenant\Domain\TenantDomainCacheKey;
use Alumateria\Contracts\Tenant\Domain\TenantDomainResolverInterface;
use Alumateria\Contracts\Tenant\Http\TenantRequestSubscriber;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TenantDomainResolutionTest extends TestCase
{
    public const TENANT = 'b0000000-0000-4000-8000-000000000002';

    public function testCacheKeyIsSharedAndCaseInsensitive(): void
    {
        $this->assertSame(
            'alumateria:tenant_domain:stok-chemia.eu',
            TenantDomainCacheKey::for('Stok-Chemia.EU'),
        );
    }

    public function testResolverWithoutDsnResolvesNothing(): void
    {
        $this->assertNull((new PredisTenantDomainResolver(null))->resolve('stok-chemia.eu'));
        $this->assertNull((new PredisTenantDomainResolver(''))->resolve('stok-chemia.eu'));
    }

    public function testSubscriberFallsBackToHostResolution(): void
    {
        $resolver = new class implements TenantDomainResolverInterface {
            public ?string $askedHost = null;

            public function resolve(string $host): ?TenantId
            {
                $this->askedHost = $host;

                return TenantId::fromString(TenantDomainResolutionTest::TENANT);
            }
        };

        $context = new TenantContext();
        $request = Request::create('https://nowy-sklep.example/produkty');
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        (new TenantRequestSubscriber($context, $resolver))->onKernelRequest($event);

        $this->assertSame('nowy-sklep.example', $resolver->askedHost);
        $this->assertSame(self::TENANT, $context->get()->value);
    }

    public function testHeaderWinsOverHostResolution(): void
    {
        $resolver = $this->createMock(TenantDomainResolverInterface::class);
        $resolver->expects($this->never())->method('resolve');

        $context = new TenantContext();
        $request = Request::create('https://nowy-sklep.example/', server: [
            'HTTP_X_TENANT_ID' => 'a0000000-0000-4000-8000-000000000001',
        ]);
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        (new TenantRequestSubscriber($context, $resolver))->onKernelRequest($event);

        $this->assertSame('a0000000-0000-4000-8000-000000000001', $context->get()->value);
    }

    public function testUnresolvedHostLeavesContextUnset(): void
    {
        $resolver = $this->createMock(TenantDomainResolverInterface::class);
        $resolver->method('resolve')->willReturn(null);

        $context = new TenantContext();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('https://obca-domena.example/'),
            HttpKernelInterface::MAIN_REQUEST,
        );

        (new TenantRequestSubscriber($context, $resolver))->onKernelRequest($event);

        $this->assertFalse($context->has());
    }
}
