<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Invoice;

/** Why a text is not an invoice number format */
enum InvoiceNumberFormatError: string
{
    case EMPTY = 'empty';
    case TOO_LONG = 'too_long';
    case UNKNOWN_TOKEN = 'unknown_token';
    case FORBIDDEN_CHARACTER = 'forbidden_character';
    case NUMBER_MISSING = 'number_missing';
    case NUMBER_REPEATED = 'number_repeated';
    case MONTH_WITHOUT_YEAR = 'month_without_year';
}
