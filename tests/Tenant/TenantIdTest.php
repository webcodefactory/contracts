<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\Tenant;

use Alumateria\Contracts\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

final class TenantIdTest extends TestCase
{
    public function testAcceptsValidUuidAndNormalizesCase(): void
    {
        $id = TenantId::fromString('A0000000-0000-4000-8000-000000000001');

        $this->assertSame('a0000000-0000-4000-8000-000000000001', $id->value);
        $this->assertSame('a0000000-0000-4000-8000-000000000001', (string) $id);
    }

    public function testRejectsNonUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantId::fromString('alumateria');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantId::fromString('');
    }

    public function testEquals(): void
    {
        $a = TenantId::fromString('a0000000-0000-4000-8000-000000000001');
        $b = TenantId::fromString('A0000000-0000-4000-8000-000000000001');
        $c = TenantId::fromString('a0000000-0000-4000-8000-000000000002');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
