<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant\Monolog;

use Alumateria\Contracts\Tenant\TenantContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds the current tenant to every log record so per-shop issues can be
 * traced in Loki/Grafana without grepping message bodies.
 */
final readonly class TenantProcessor implements ProcessorInterface
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!$this->tenantContext->has()) {
            return $record;
        }

        $record->extra['tenant_id'] = $this->tenantContext->get()->value;

        return $record;
    }
}
