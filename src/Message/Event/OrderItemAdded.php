<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class OrderItemAdded
{
    public function __construct(
        public string $orderId,
        public string $orderItemId,
        public string $productId,
        public string $offerId,
        public string $sku,
        public string $name,
        public int $qty,
        public int $unitPriceAmount,
        public int $rowTotalAmount,
        public \DateTimeImmutable $addedAt,
        public string $correlationId,
    ) {
    }
}
