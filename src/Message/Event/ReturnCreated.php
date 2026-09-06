<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * A return or complaint request was filed (by the customer or manually
 * by an admin). Carries the recipient e-mail resolved by the emitter -
 * consumers must not need a client-api lookup to send the confirmation.
 */
final class ReturnCreated
{
    public function __construct(
        public string $returnId,
        public string $returnNumber,
        public string $orderId,
        public string $clientId,
        public string $email,
        public string $clientName,
        /** 'return' | 'complaint' | 'exchange' */
        public string $type,
        public string $status,
        public int $refundAmount,
        public string $currency,
        public \DateTimeImmutable $createdAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
