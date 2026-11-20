<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

/**
 * An order is still unpaid and its customer should be reminded. The
 * deadline is the date the shop promises to wait until; an order unpaid
 * after it gets canceled.
 */
final class OrderPaymentReminder
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $paymentDeadline,
        public \DateTimeImmutable $remindedAt,
        public string $correlationId,
        public ?string $tenantId = null,
    ) {
    }
}
