<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Invoice;

final class InvalidInvoiceNumberFormat extends \InvalidArgumentException
{
    public function __construct(
        public readonly InvoiceNumberFormatError $error,
        string $message,
    ) {
        parent::__construct($message);
    }
}
