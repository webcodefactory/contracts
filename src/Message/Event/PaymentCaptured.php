<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class PaymentCaptured
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public string $method,
        public string $status,
        public \DateTimeImmutable $capturedAt,
        public string $correlationId,
    ) {
    }
}
