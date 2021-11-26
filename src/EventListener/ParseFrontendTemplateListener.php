<?php
namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\ThemeModel;
use Netzhirsch\CookieOptInBundle\Blocker\AnalyticsBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\CustomGmapBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\IFrameBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\ScriptBlocker;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netzhirsch\CookieOptInBundle\Blocker\VideoPreviewBlocker;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\RevokeRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ParseFrontendTemplateListener
{
    /**
     * @param $buffer
     * @param $template
     * @return string
     * @throws DBALException|Exception
     * @throws \Exception
     */
    public function onParseFrontendTemplate($buffer, $template)
    {
        global $objPage;
        //On Backend empty
        if (empty($objPage))
            return $buffer;

        if (PageLayoutListener::isDisabled($objPage))
            return $buffer;

        if (!$this->isBarInLayoutOrPage($objPage))
            return $buffer;

        if (!empty($buffer) && !PageLayoutListener::shouldRemoveModules($objPage)) {
            if (
                strpos($buffer, '<iframe') !== false
                && strpos($buffer, '<figure class="video_container">') == false
            ) {
                if (
                    strpos($template, 'ce_html') !== false
                    || strpos($template, 'ce_text') !== false
                    || strpos($template, 'ce_youtube') !== false
                    || strpos($template, 'ce_vimeo') !== false
                    || strpos($template, 'ce_metamodel_list') !== false
                ) {
                    $iframeBlocker = new IFrameBlocker();
                    return $iframeBlocker->iframe($buffer,$this->getConnection(),$this->getRequestStack());
                }
            } elseif(
                strpos($buffer, '<iframe') !== false
                && strpos($buffer, '<figure class="video_container">') !== false
                && strpos($template, 'mod') === false
                && strpos($template, $objPage->template) === false

            ) {
                $videoPreviewBlocker = new VideoPreviewBlocker();
                return $videoPreviewBlocker->iframe($buffer,$this->getConnection(),$this->getRequestStack());
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
                    || strpos($template, 'mod_matomo_Tracking') !== false
            );

            if ($isAnalyticsTemplateMatomo) {
                $analyticsBlocker = new AnalyticsBlocker();
                return $analyticsBlocker->analyticsTemplate($buffer,'matomo');
            }

            $isScriptTemplate =
                   $template == 'ce_html' && strpos($buffer, '<script') !== false
                || strpos($template, 'script_to_block') !== false
            ;
            if ($isScriptTemplate) {
                $scriptBlocker = new ScriptBlocker();
                return $scriptBlocker->script($buffer,$this->getConnection(),$this->getRequestStack());
            }

            $isCustomElementGmapTemplate = strpos($template, 'customelement_gmap') !== false || strpos($template, 'mod_catalog_map_default') !== false;
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

    private function isBarInLayoutOrPage($objPage){

        if ($this->checkModulesEmpty(LayoutModel::findById($objPage->layout)))
            return true;

        if ($this->checkModulesEmpty($objPage))
            return true;

        return false;

    }

    public static function checkModulesEmpty($layoutOrPage) {
        /**TODO luhmann return überarbeiten damit die Funktion öfter genutzt werden kann
        $layoutModules = StringUtil::deserialize($layoutOrPage->__get('modules'));
        /** @var Connection $conn */
        $conn = System::getContainer()->get('database_connection');
        $barRepository = new BarRepository($conn);

        if (!empty($layoutModules)) {
            $bars = $barRepository->findAll();
            $revokeRepository = new RevokeRepository($conn);

            foreach ($layoutModules as $key => $layoutModule) {
                if (!empty($layoutModule['enable'])) {
                    if (!empty($bars)) {
                        foreach ($bars as $bar) {
                            if ($bar['pid'] == $layoutModule['mod']) {
                                return true;
                            }
                        }
                    }
                    $revokes = $revokeRepository->findByPid($layoutModule['mod']);
                    if (!empty($revokes)) {
                        foreach ($revokes as $revoke) {
                            if ($revoke['pid'] == $layoutModule['mod']) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        $pageId = $layoutOrPage->__get('id');
        $bars = $barRepository->findByLayoutOrPage($pageId);
        if (!empty($bars)) {
            return true;
        } elseif(get_class($layoutOrPage) == PageModel::class) {

            global $objPage;
            // Get the page layout
            $objLayout = LayoutModel::findByPk($objPage->layout);
            $test = $objPage->loadDetails();

            /** @var ThemeModel $objTheme */
            $objTheme = $objLayout->getRelated('pid');

            // Set the layout template and template group
            $template = $objLayout->template ?: 'fe_page';
            $templateGroup = $objTheme->templates ?? '';

            $dir = TL_ROOT;
            $dir .= DIRECTORY_SEPARATOR;
            if (!empty($templateGroup)) {
                $dir .=
                    $templateGroup
                    .DIRECTORY_SEPARATOR
                ;
            }
            $dir .=
                'templates'
                .DIRECTORY_SEPARATOR
                .$template
                .'.html5'
            ;
            $modId = null;
            if (file_exists($dir)) {
                $content = file_get_contents($dir);
                $modId = PageLayoutListener::getModuleIdFromTemplate($content,$conn);
            }
            if (!empty($modId)) {
                return true;
            }
        }

        return false;
    }
}
