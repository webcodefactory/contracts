<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant\Http;

use Alumateria\Contracts\Tenant\Http\TenantAccessSubscriber;
use Alumateria\Contracts\Tenant\TenantAwareUserInterface;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

final class TenantAccessSubscriberTest extends TestCase
{
    private const TENANT_A = 'a0000000-0000-4000-8000-000000000001';
    private const TENANT_B = 'b0000000-0000-4000-8000-000000000002';

    /** @param list<string> $tenantIds */
    private function adminUser(array $tenantIds): UserInterface
    {
        return new class($tenantIds) implements UserInterface, TenantAwareUserInterface {
            /** @param list<string> $tenantIds */
            public function __construct(private readonly array $tenantIds)
            {
            }

            public function getTenantIds(): array
            {
                return $this->tenantIds;
            }

            public function getRoles(): array
            {
                return ['ROLE_ADMIN'];
            }

            public function getUserIdentifier(): string
            {
                return 'admin';
            }

            public function eraseCredentials(): void
            {
            }
        };
    }

    private function dispatch(TenantContext $context, ?UserInterface $user, array $roles = ['ROLE_ADMIN']): void
    {
        $storage = new TokenStorage();
        if ($user !== null) {
            $storage->setToken(new UsernamePasswordToken($user, 'main', $roles));
        }

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        (new TenantAccessSubscriber($storage, $context))->onKernelRequest($event);
    }

    public function testAdminOfCurrentTenantPasses(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString(self::TENANT_A));

        $this->dispatch($context, $this->adminUser([self::TENANT_A, self::TENANT_B]));

        $this->addToAssertionCount(1); // no exception
    }

    public function testAdminOfAnotherTenantIsDenied(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString(self::TENANT_A));

        $this->expectException(AccessDeniedHttpException::class);

        $this->dispatch($context, $this->adminUser([self::TENANT_B]));
    }

    public function testAdminWithoutMembershipsIsDenied(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString(self::TENANT_A));

        $this->expectException(AccessDeniedHttpException::class);

        $this->dispatch($context, new InMemoryUser('admin', null, ['ROLE_ADMIN']));
    }

    public function testAdminWithUnresolvedTenantIsDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->dispatch(new TenantContext(), $this->adminUser([self::TENANT_A]));
    }

    public function testServiceTokenIsExempt(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString(self::TENANT_A));

        $this->dispatch($context, new InMemoryUser('svc', null, ['ROLE_SERVICE']), ['ROLE_SERVICE']);

        $this->addToAssertionCount(1); // no exception
    }

    public function testAnonymousRequestIsIgnored(): void
    {
        $this->dispatch(new TenantContext(), null);

        $this->addToAssertionCount(1);
    }
}
