<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class PaymentFailed
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public string $reason,
        public \DateTimeImmutable $failedAt,
        public string $correlationId,
    ) {
    }
}
