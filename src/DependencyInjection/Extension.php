<?php

namespace Netzhirsch\CookieOptInBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension as BaseExtension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Extension extends BaseExtension
{
	/**
	 * {@inheritdoc}
	 * @throws Exception
	 */
    public function load(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        // Listener reagieren auf events / hooks
        $loader->load('listener.yml');
    }
}
