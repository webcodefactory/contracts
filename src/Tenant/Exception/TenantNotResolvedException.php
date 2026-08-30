<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Exception;

/**
 * Thrown when tenant-scoped code runs without a resolved tenant.
 * This is fail-closed by design: an unset context must never silently
 * behave like "no tenant filtering".
 */
final class TenantNotResolvedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Tenant not resolved. HTTP requests must carry the gateway-issued X-Tenant-Id header; '
            . 'message handlers require a TenantStamp; CLI commands must set the tenant explicitly.'
        );
    }
}
