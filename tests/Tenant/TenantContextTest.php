<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant;

use Alumateria\Contracts\Tenant\Exception\TenantNotResolvedException;
use Alumateria\Contracts\Tenant\TenantContext;
use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

final class TenantContextTest extends TestCase
{
    public function testGetThrowsWhenUnresolved(): void
    {
        $this->expectException(TenantNotResolvedException::class);

        (new TenantContext())->get();
    }

    public function testSetGetHasClear(): void
    {
        $context = new TenantContext();
        $this->assertFalse($context->has());

        $id = TenantId::fromString('a0000000-0000-4000-8000-000000000001');
        $context->set($id);

        $this->assertTrue($context->has());
        $this->assertTrue($context->get()->equals($id));

        $context->clear();
        $this->assertFalse($context->has());
    }
}
