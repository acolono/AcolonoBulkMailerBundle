<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Only loaded because DependencyInjection/AcolonoBulkMailerExtension.php
// exists (Symfony's Bundle::getContainerExtension() convention). Without it
// this file is silently ignored and no plugin service is registered.
return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->public();

    // RecipientSource/* excluded: implementations take runtime constructor
    // args (file path, ...) and are always instantiated manually.
    $services->load('MauticPlugin\\AcolonoBulkMailerBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, ['AcolonoBulkMailerBundle.php', 'RecipientSource'])).'}');
};
