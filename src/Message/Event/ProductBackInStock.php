<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * A product a customer subscribed to is available again. Emitted by the
 * client-api stock-notification processor; notification-api mails the
 * subscriber (subject to their notification preferences).
 */
final class ProductBackInStock
{
    public function __construct(
        public string $clientId,
        public string $email,
        public string $name,
        public string $productUuid,
        public string $productName,
        public \DateTimeImmutable $detectedAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
