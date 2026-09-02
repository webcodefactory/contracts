<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tenant;

use Alumateria\Contracts\Tenant\Doctrine\TenantFilterConfigurator;
use Alumateria\Contracts\Tenant\Domain\PredisTenantDomainResolver;
use Alumateria\Contracts\Tenant\Domain\TenantDomainResolverInterface;
use Alumateria\Contracts\Tenant\Http\TenantAccessSubscriber;
use Alumateria\Contracts\Tenant\Http\TenantHeaders;
use Alumateria\Contracts\Tenant\Http\TenantRequestSubscriber;
use Alumateria\Contracts\Tenant\Messenger\TenantContextMiddleware;
use Alumateria\Contracts\Tenant\Monolog\TenantProcessor;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Registers the tenant plumbing in a consuming service:
 *  - TenantContext singleton,
 *  - HTTP subscriber resolving the tenant from the gateway header,
 *  - Monolog processor adding tenant_id to log records,
 *  - Messenger middleware (registered as a service; each service adds it
 *    to its bus middleware list in messenger.yaml).
 *
 * Usage: add TenantBundle to bundles.php.
 */
final class TenantBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $tenantContext = $services->set(TenantContext::class);

        // Doctrine safety net: sync the tenant SQL filter with the context.
        // Only in services that actually use the ORM (notification-api etc.
        // have no DoctrineBundle). Checked via kernel.bundles because
        // loadExtension receives a temporary builder without extensions.
        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];
        if (is_array($bundles) && array_key_exists('DoctrineBundle', $bundles)) {
            $services->set(TenantFilterConfigurator::class)
                ->args([service('doctrine')]);

            $tenantContext->call('subscribe', [service(TenantFilterConfigurator::class)]);
        }

        // Host->tenant fallback for dynamic shop domains (faza B): reads
        // the shared Redis mapping written by settings-api. Requires
        // predis; services without it (pure workers) resolve by header only.
        $resolver = null;
        if (class_exists(\Predis\Client::class)) {
            $services->set(PredisTenantDomainResolver::class)
                ->args(['%env(default::REDIS_URL)%']);
            $services->alias(TenantDomainResolverInterface::class, PredisTenantDomainResolver::class);
            $resolver = service(PredisTenantDomainResolver::class);
        }

        $services->set(TenantRequestSubscriber::class)
            ->args([service(TenantContext::class), $resolver])
            ->tag('kernel.event_subscriber');

        $services->set(TenantHeaders::class)
            ->args([service(TenantContext::class)]);

        // Admin membership guard - only where SecurityBundle provides the
        // token storage
        if (is_array($bundles) && array_key_exists('SecurityBundle', $bundles)) {
            $services->set(TenantAccessSubscriber::class)
                ->args([service('security.token_storage'), service(TenantContext::class)])
                ->tag('kernel.event_subscriber');
        }

        $services->set(TenantProcessor::class)
            ->args([service(TenantContext::class)])
            ->tag('monolog.processor');

        $services->set(TenantContextMiddleware::class)
            ->args([service(TenantContext::class)]);

        // Allow constructor injection of TenantContext in application services
        $services->alias('alumateria.tenant_context', TenantContext::class);
    }
}
