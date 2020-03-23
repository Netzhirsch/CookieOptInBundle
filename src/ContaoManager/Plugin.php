<?php

namespace Netzhirsch\CookieOptInBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Exception;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

// Für getBundles()
use Netzhirsch\CookieOptInBundle\NetzhirschCookieOptInBundle;
use Contao\CoreBundle\ContaoCoreBundle;

/**
 * Plugin for the Contao Manager.
 *
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
	
	    /** @noinspection PhpParamsInspection aspect string got string*/
	    return [
            BundleConfig::create(NetzhirschCookieOptInBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class,])
        ];

    }
	
	/**
	 * {@inheritdoc}
	 * @throws Exception
	 */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__.'/../Resources/config/routing.yml')
            ->load(__DIR__.'/../Resources/config/routing.yml')
        ;
    }

	/**
	 * Allows a plugin to override extension configuration.
	 *
	 * @param string $extensionName
	 * @param array  $extensionConfigs
	 *
	 * @return array
	 */
    public function getExtensionConfig($extensionName, array $extensionConfigs)
    {
        /**
         * Füge dein Bundle zu Doctrine hinzu
         * Ab Contao 4.8 ist das nicht mehr notwendig
         * (Kernel::VERSION < '4.3' ist ab Contao 4.8 TRUE)
         */
        if ('doctrine' === $extensionName && Kernel::VERSION < '4.3') 
        {    
            // Mit Contao 4.8 ist das nicht mehr notwendig
            $extensionConfigs[0]['orm']['entity_managers']['default']['mappings']['NetzhirschCookieOptInBundle'] = "";
        }

        return $extensionConfigs;
    }

}

