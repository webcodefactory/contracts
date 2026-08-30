<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant;

/**
 * Identifier of a tenant (a single shop/organisation on the platform).
 * Always a valid UUID - enforced in the named constructor, so past this
 * point the value can be trusted everywhere.
 */
final readonly class TenantId implements \Stringable
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower($value);

        if (preg_match(self::UUID_PATTERN, $normalized) !== 1) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid tenant id (UUID expected)', $value));
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
