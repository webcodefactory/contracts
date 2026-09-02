<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Domain;

use Alumateria\Contracts\Tenant\TenantId;
use Predis\Client;

/**
 * Domain->tenant resolution backed by the shared Redis (written by
 * settings-api on domain CRUD). Lazy: the connection is opened on first
 * use; an empty DSN (service without Redis) resolves nothing, which
 * keeps the request fail-closed at the point of tenant use.
 */
final class PredisTenantDomainResolver implements TenantDomainResolverInterface
{
    private ?Client $client = null;

    /** @var array<string, ?TenantId> per-request memo */
    private array $memo = [];

    public function __construct(
        private readonly ?string $redisDsn,
    ) {
    }

    public function resolve(string $host): ?TenantId
    {
        $host = strtolower($host);

        if (array_key_exists($host, $this->memo)) {
            return $this->memo[$host];
        }

        if ($this->redisDsn === null || $this->redisDsn === '') {
            return $this->memo[$host] = null;
        }

        try {
            $value = $this->client()->get(TenantDomainCacheKey::for($host));
            $tenantId = is_string($value) && $value !== '' ? TenantId::fromString($value) : null;
        } catch (\Throwable) {
            // Redis down or corrupted mapping: unresolved tenant, callers
            // fail closed - never a guessed default
            $tenantId = null;
        }

        return $this->memo[$host] = $tenantId;
    }

    private function client(): Client
    {
        return $this->client ??= new Client($this->redisDsn);
    }
}
