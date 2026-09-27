<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Fixture;

enum SampleStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
