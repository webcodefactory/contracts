<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Fixture;

use Alumateria\Contracts\DataExchange\DatasetInterface;

enum SampleDataset: string implements DatasetInterface
{
    case PRODUCTS = 'products';
    case CATEGORIES = 'categories';
    case ATTRIBUTES = 'attributes';

    public function key(): string
    {
        return $this->value;
    }

    public function filenameStem(): string
    {
        return match ($this) {
            self::PRODUCTS => 'produkty',
            self::CATEGORIES => 'kategorie',
            self::ATTRIBUTES => 'atrybuty',
        };
    }
}
