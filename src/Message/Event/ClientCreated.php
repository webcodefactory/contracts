<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Message\Event;

final class ClientCreated
{
    public function __construct(
        public string $clientId,
        public string $name,
        public string $email,
        public \DateTimeImmutable $createdAt,
        public string $correlationId,
    ) {
    }
}
