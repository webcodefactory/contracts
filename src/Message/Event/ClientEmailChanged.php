<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * A customer's login e-mail HAS been changed. Consumers notify the OLD
 * address - the safety net that lets the real owner react if a hijacked
 * session was used to take over the account.
 */
final class ClientEmailChanged
{
    public function __construct(
        public string $clientId,
        public string $oldEmail,
        public string $newEmail,
        public string $name,
        public \DateTimeImmutable $changedAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
