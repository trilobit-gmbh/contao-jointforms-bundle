<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class JointformsExtension extends Extension
{
    public function getAlias(): string
    {
        return 'trilobit';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $bundleConfig = [];

        foreach ($configs as $subConfig) {
            $bundleConfig = array_merge($bundleConfig, $subConfig);
        }

        if (isset($bundleConfig['jointforms'])) {
            $container->setParameter('trilobit.jointforms', $bundleConfig['jointforms']);
        }

        /*
        $configuration = new Configuration();

        $bundleConfig = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (isset($bundleConfig['jointforms'])) {
            $container->setParameter('trilobit.jointforms', $bundleConfig['jointforms']);
        }
        */
    }
}
