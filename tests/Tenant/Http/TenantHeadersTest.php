<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant\Http;

use Alumateria\Contracts\Tenant\Exception\TenantNotResolvedException;
use Alumateria\Contracts\Tenant\Http\TenantHeaders;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

final class TenantHeadersTest extends TestCase
{
    public function testBuildsHeaderFromCurrentTenant(): void
    {
        $context = new TenantContext();
        $context->set(TenantId::fromString('a0000000-0000-4000-8000-000000000001'));

        $this->assertSame(
            ['X-Tenant-Id' => 'a0000000-0000-4000-8000-000000000001'],
            (new TenantHeaders($context))->asArray(),
        );
    }

    public function testFailsClosedWithoutTenant(): void
    {
        $this->expectException(TenantNotResolvedException::class);

        (new TenantHeaders(new TenantContext()))->asArray();
    }
}
