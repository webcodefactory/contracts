<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class OrderCreated
{
    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public string $clientId,
        public int $grandTotalAmount,
        public string $currency,
        public \DateTimeImmutable $createdAt,
        public string $correlationId,
    ) {
    }
}
