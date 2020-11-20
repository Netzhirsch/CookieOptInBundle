<?php
namespace Netzhirsch\CookieOptInBundle\EventListener;

use Netzhirsch\CookieOptInBundle\Blocker\AnalyticsBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\CustomGmapBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\IFrameBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\ScriptBlocker;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ParseFrontendTemplateListener
{
    /**
     * @param $buffer
     * @param $template
     * @return string
     * @throws DBALException|Exception
     */
    public function onParseFrontendTemplate($buffer, $template)
    {
        // Nur passende Template untersuchen um Zeit zu sparen
        if (!empty($buffer)) {
            if (strpos($buffer, '<iframe') !== false) {
                if (
                    strpos($template, 'ce_html') !== false
                    || strpos($template, 'ce_youtube') !== false
                    || strpos($template, 'ce_vimeo') !== false
                    || strpos($template, 'ce_metamodel_list') !== false
                ) {
                    $iframeBlocker = new IFrameBlocker();
                    return $iframeBlocker->iframe($buffer,$this->getConnection(),$this->getRequestStack());
                }
            }


            $isAnalyticsTemplateGoogle = (strpos($template, 'analytics_google') !== false);
            if ($isAnalyticsTemplateGoogle) {
                $analyticsBlocker = new AnalyticsBlocker();
                return $analyticsBlocker->analyticsTemplate($buffer,'googleAnalytics');
            }

            $isAnalyticsTemplateMatomo
                = (
                    strpos($template, 'analytics_piwik') !== false
                    || strpos($template, 'analytics_matomo') !== false
            );

            if ($isAnalyticsTemplateMatomo) {
                $analyticsBlocker = new AnalyticsBlocker();
                return $analyticsBlocker->analyticsTemplate($buffer,'matomo');
            }

            $isScriptTemplate = $template == 'ce_html' && strpos($buffer, '<script') !== false;
            if ($isScriptTemplate) {
                $scriptBlocker = new ScriptBlocker();
                return $scriptBlocker->script($buffer,$this->getConnection(),$this->getRequestStack());
            }

            $isCustomElementGmapTemplate = strpos($template, 'customelement_gmap') !== false;
            if ($isCustomElementGmapTemplate) {
                $customGmapBlocker = new CustomGmapBlocker();
                return $customGmapBlocker->block($buffer,$this->getConnection(),$this->getRequestStack());
            }
        }

        // nichts ändern
        return $buffer;
    }


    /**
     *
     * @return Connection|object
     */
    private function getConnection() {
        $container = $this->getContainer();
        return $container->get('database_connection');
    }

    /**
     * @return object|RequestStack|null
     */
    private function getRequestStack() {
        $container = $this->getContainer();
        return $container->get('request_stack');
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer() {
        return System::getContainer();
    }


}
