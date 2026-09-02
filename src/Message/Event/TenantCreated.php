<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * A new shop (tenant) was created on the platform. Consumers provision
 * their per-tenant defaults (payment methods, price list, warehouse...).
 *
 * IMPORTANT for handlers: the TenantStamp on this message carries the
 * tenant of the OPERATOR who created the shop, not the new shop itself.
 * Provisioning handlers must set the TenantContext explicitly from the
 * payload tenantId before creating any records.
 */
final class TenantCreated
{
    public function __construct(
        public string $tenantId,
        public string $code,
        public string $name,
        public string $defaultCurrency,
        public string $defaultLocale,
        public \DateTimeImmutable $occurredAt,
        public string $correlationId,
    ) {
    }
}
