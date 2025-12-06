<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class ClientUpdated
{
    public function __construct(
        public string $clientId,
        public ?string $name,
        public ?string $email,
        public \DateTimeImmutable $updatedAt,
        public string $correlationId,
    ) {
    }
}
