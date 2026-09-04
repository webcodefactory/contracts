<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * A storefront customer (or a guest claiming their account) asked for a
 * password reset link. Carries the RAW one-time token - client-api stores
 * only its hash; notification-api turns it into the reset URL using the
 * tenant's storeUrl setting.
 */
final class ClientPasswordResetRequested
{
    public function __construct(
        public string $clientId,
        public string $email,
        public string $name,
        public string $resetToken,
        public \DateTimeImmutable $expiresAt,
        public \DateTimeImmutable $requestedAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
