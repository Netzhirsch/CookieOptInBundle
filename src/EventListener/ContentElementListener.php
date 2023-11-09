<?php
namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Database;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\ThemeModel;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Netzhirsch\CookieOptInBundle\Blocker\AnalyticsBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\CustomGmapBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\IFrameBlocker;
use Netzhirsch\CookieOptInBundle\Blocker\ScriptBlocker;
use Contao\System;
use Netzhirsch\CookieOptInBundle\Blocker\VideoPreviewBlocker;
use Netzhirsch\CookieOptInBundle\Entity\CookieToolContainer;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolContainerRepository;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolRepository;
use Netzhirsch\CookieOptInBundle\Repository\RevokeRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentElementListener
{
    /** @var Database $database */
    private $database;

    public function __construct(
        private readonly ParameterBag $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly CookieToolRepository $cookieToolRepository,
        private readonly InsertTagParser $insertTagParser
    )
    {
        $this->database = Database::getInstance();
    }

    /**
     * @param ContentModel $contentModel
     * @param string       $buffer
     *
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function onContentElement(ContentModel $contentModel,string $buffer): string
    {
        global $objPage;
        $template = $contentModel->typePrefix.$contentModel->type;
        $sourceId = $contentModel->pid;
        if (
            empty($objPage)
            || empty($buffer)
            || (
                !str_contains($buffer, '<iframe')
                && !str_contains($buffer, '<figure class="video_container">')
                && !str_contains($template, 'analytics_google')
                && !str_contains($template, 'mod_matomo_Tracking')
                && !str_contains($template, 'analytics_matomo')
                && !str_contains($template, 'analytics_piwik')
                && !str_contains($template, 'ce_html')
                && !str_contains($template, 'ce_unfiltered_html')
                && !str_contains($template, 'customelement_gmap')
                && !str_contains($template, 'mod_catalog_map_default')
                && !str_contains($template, 'script_to_block')
                && !str_contains($template, 'ce_module')
            )
        ) {
            return $buffer;
        }


        if (PageLayoutListener::isDisabled($objPage))
            return $buffer;

        if (!$this->isBarInLayoutOrPage($objPage))
            return $buffer;

        if (!empty($buffer) && !PageLayoutListener::shouldRemoveModules($objPage)) {
            if (
                str_contains($buffer, '<iframe')
                && !strpos($buffer, '<figure class="video_container">')
                && !strpos($buffer, 'data-splash-screen')
            ) {
                if (
                    str_contains($template, 'ce_html')
                    || str_contains($template, 'ce_text')
                    || str_contains($template, 'ce_youtube')
                    || str_contains($template, 'ce_vimeo')
                    || str_contains($template, 'ce_metamodel_list')
                    || str_contains($template, 'rsce_luxe_map')
                    || str_contains($template, 'ce_unfiltered_html')
                    || str_contains($template, 'ce_module')
                ) {
                    $iframeBlocker = new IFrameBlocker();
                    return $iframeBlocker->iframe(
                        $buffer,
                        $this->database,
                        $this->getRequestStack(),
                        $this->parameterBag,
                        $this->cookieToolRepository,
                        $sourceId,
                        $this->insertTagParser
                    );
                }
            } elseif (
                str_contains($buffer, 'data-splash-screen')
                && !str_contains($template, 'mod')
                && !str_contains($template, $objPage->template)

            ) {
                if (strpos($template, 'news') > 0)
                    return $buffer;
                if ($template == 'ce_youtube') {
                    $videoPreviewBlocker = new VideoPreviewBlocker();
                    return $videoPreviewBlocker->iframe(
                        $buffer,
                        $this->database,
                        $this->getRequestStack(),
                        $this->parameterBag,
                        $this->cookieToolRepository,
                        $sourceId,
                        $this->insertTagParser
                    );
                }

            }


            $isAnalyticsTemplateGoogle = (str_contains($template, 'analytics_google'));
            if ($isAnalyticsTemplateGoogle) {
                $analyticsBlocker = new AnalyticsBlocker();
                return $analyticsBlocker->analyticsTemplate($buffer,'googleAnalytics');
            }

            $isAnalyticsTemplateMatomo
                = (
                str_contains($template, 'analytics_piwik')
                || str_contains($template, 'analytics_matomo')
                || str_contains($template, 'mod_matomo_Tracking')
            );

            if ($isAnalyticsTemplateMatomo) {
                $analyticsBlocker = new AnalyticsBlocker();
                return $analyticsBlocker->analyticsTemplate($buffer,'matomo');
            }

            $isScriptTemplate =
                ($template == 'ce_html' && str_contains($buffer, '<script'))
                   || str_contains($template, 'script_to_block')
                   || ($template == 'ce_unfiltered_html' && str_contains($buffer, '<script'))
            ;
            if ($isScriptTemplate) {
                $scriptBlocker = new ScriptBlocker();
                return $scriptBlocker->script(
                    $buffer,
                    $this->database,
                    $this->getRequestStack(),
                    $this->parameterBag,
                    $this->cookieToolRepository,
                    $this->insertTagParser
                );
            }

            $isCustomElementGmapTemplate = str_contains($template, 'customelement_gmap') || str_contains($template, 'mod_catalog_map_default');
            if ($isCustomElementGmapTemplate) {
                $customGmapBlocker = new CustomGmapBlocker();
                return $customGmapBlocker->block(
                    $buffer,
                    $this->database,
                    $this->getRequestStack(),
                    $this->parameterBag,
                    $this->cookieToolRepository,
                    $this->insertTagParser
                );
            }
        }

        // nichts ändern
        return $buffer;
    }

    /**
     * @return RequestStack|null
     */
    private function getRequestStack(): RequestStack|null
    {
        $container = $this->getContainer();
        return $container->get('request_stack');
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer(): ContainerInterface
    {
        return System::getContainer();
    }

    private function isBarInLayoutOrPage($objPage): bool
    {

        $layout = LayoutModel::findById($objPage->layout);
        if ($this->checkModulesEmpty($layout))
            return true;

        if ($this->checkModulesEmpty($objPage))
            return true;

        $data = PageLayoutListener::getModuleIdFromInsertTag($objPage, $layout, $this->database);
        if (!empty($data['moduleIds']))
            return true;

        return false;

    }

    public function checkModulesEmpty($layoutOrPage): bool
    {
        $layoutModules = unserialize($layoutOrPage->__get('modules'));
        $conn = $this->database;
        /** @var CookieToolContainerRepository $repoCookieToolContainer */
        $repoCookieToolContainer = $this->entityManager->getRepository(CookieToolContainer::class);
        if (!empty($layoutModules)) {
            $revokeRepository = new RevokeRepository($conn);
            foreach ($layoutModules as $layoutModule) {
                if (!empty($layoutModule['enable'])) {
                    $cookieToolContainer = $repoCookieToolContainer->findOneBy(['sourceId' => $layoutModule['mod']]);
                    if (!empty($cookieToolContainer)) {
                        return true;
                    }
                    if (isset($layoutModule['languageSwitch'])) {
                        $languageSwitch = $layoutModule['languageSwitch'];
                        $languageSwitch = unserialize($languageSwitch);
                        if (isset($languageSwitch[0]) && $languageSwitch[0]['mod'] == $layoutModule['mod'])
                            return true;
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
        $barRepository = new BarRepository($this->database);
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

            $dir = $this->parameterBag->get('kernel.project_dir');
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
