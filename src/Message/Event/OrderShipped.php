<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class OrderShipped
{
    /**
     * @param array<int, array{productId: string, offerId: string, sku: string, qty: int}> $items
     */
    public function __construct(
        public string $orderId,
        public array $items,
        public \DateTimeImmutable $shippedAt,
        public string $correlationId,
        public int $grandTotalAmount = 0,
        public string $currency = 'PLN',
    ) {
    }
}