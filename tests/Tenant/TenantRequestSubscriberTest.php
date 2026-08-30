<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant;

use Alumateria\Contracts\Tenant\Http\TenantRequestSubscriber;
use Alumateria\Contracts\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TenantRequestSubscriberTest extends TestCase
{
    private function dispatch(TenantContext $context, Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): void
    {
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);

        (new TenantRequestSubscriber($context))->onKernelRequest($event);
    }

    public function testResolvesTenantFromHeader(): void
    {
        $context = new TenantContext();
        $request = new Request(server: ['HTTP_X_TENANT_ID' => 'a0000000-0000-4000-8000-000000000001']);

        $this->dispatch($context, $request);

        $this->assertSame('a0000000-0000-4000-8000-000000000001', $context->get()->value);
    }

    public function testLeavesContextUnsetWithoutHeader(): void
    {
        $context = new TenantContext();

        $this->dispatch($context, new Request());

        $this->assertFalse($context->has());
    }

    public function testRejectsMalformedHeader(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->dispatch(new TenantContext(), new Request(server: ['HTTP_X_TENANT_ID' => 'not-a-uuid']));
    }

    public function testIgnoresSubRequests(): void
    {
        $context = new TenantContext();
        $request = new Request(server: ['HTTP_X_TENANT_ID' => 'a0000000-0000-4000-8000-000000000001']);

        $this->dispatch($context, $request, HttpKernelInterface::SUB_REQUEST);

        $this->assertFalse($context->has());
    }
}
