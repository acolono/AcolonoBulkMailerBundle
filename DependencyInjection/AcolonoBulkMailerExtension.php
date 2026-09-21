<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Required for Symfony's Bundle::getContainerExtension() to find and load
 * Config/services.php at all — without it, no plugin service is registered.
 */
class AcolonoBulkMailerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');
    }
}
