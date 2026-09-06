<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * The status of a return/complaint changed (admin decision). Carries the
 * recipient e-mail resolved by the emitter.
 */
final class ReturnStatusChanged
{
    public function __construct(
        public string $returnId,
        public string $returnNumber,
        public string $clientId,
        public string $email,
        public string $clientName,
        /** 'return' | 'complaint' | 'exchange' */
        public string $type,
        public string $status,
        public ?string $resolution,
        public int $refundAmount,
        public string $currency,
        public \DateTimeImmutable $changedAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
