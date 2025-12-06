<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class OrderStatusChanged
{
    public function __construct(
        public string $orderId,
        public string $oldStatus,
        public string $newStatus,
        public \DateTimeImmutable $changedAt,
        public string $correlationId,
    ) {
    }
}
