<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

use Letkode\ConfigPublisherBundle\Command\PublishCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LetkodeConfigPublisherBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        // Built when the command runs, so it always sees the packages installed right now.
        $services->set(PublishableDiscovery::class)
            ->factory([PublishableDiscovery::class, 'fromComposer']);

        $services->set(Publisher::class)
            ->args([param('kernel.project_dir')]);

        $services->set(PublishCommand::class)
            ->args([service(PublishableDiscovery::class), service(Publisher::class)])
            ->tag('console.command', ['command' => PublishCommand::NAME]);
    }
}
