<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class PaymentStatusChanged
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public string $oldStatus,
        public string $newStatus,
        public \DateTimeImmutable $changedAt,
        public string $correlationId,
    ) {
    }
}
