<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class OrderCanceled
{
    /**
     * @param array<int, array{productId: string, offerId: string, sku: string, qty: int}> $items
     */
    public function __construct(
        public string $orderId,
        public array $items,
        public string $reason,
        public \DateTimeImmutable $canceledAt,
        public string $correlationId,
        )
    {
    }
}