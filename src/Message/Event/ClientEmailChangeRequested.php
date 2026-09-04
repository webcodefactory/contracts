<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * An authenticated customer asked to change their login e-mail. The
 * confirmation link goes to the NEW address (proves mailbox ownership);
 * the account e-mail changes only after the token is confirmed. Carries
 * the RAW one-time token - client-api stores only its hash.
 */
final class ClientEmailChangeRequested
{
    public function __construct(
        public string $clientId,
        public string $currentEmail,
        public string $newEmail,
        public string $name,
        public string $confirmToken,
        public \DateTimeImmutable $expiresAt,
        public \DateTimeImmutable $requestedAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
