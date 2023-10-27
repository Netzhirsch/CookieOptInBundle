<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Database;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\ORM\NonUniqueResultException;
use DOMDocument;
use Exception;
use Netzhirsch\CookieOptInBundle\Resources\contao\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolRepository;
use Netzhirsch\CookieOptInBundle\Repository\ModuleRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class Blocker
{

    /**
     * @throws Exception
     */
    public static function getModulData(RequestStack $requestStack,Database $database,ParameterBag $parameterBag) {
        $moduleData = [];
        $attributes = $requestStack->getCurrentRequest()->attributes;
        if (empty($attributes))
            return $moduleData;

        /**
         * Aus dem PageModel die ModulIds finden, damit diese mit dem PID der CookieBar verglichen werden kann.
         * Stimmt die PID ,so ist diese Cookiebar für dieses Modul gedacht.
         */
        /** @var PageModel $pageModel */
        $pageModel = $attributes->get('pageModel');
        // Contao 4.4
        if (empty($pageModel))
            $pageModel = $GLOBALS['objPage'];

        // Achtung moduleData enthält nur die ID
         if (is_string($pageModel))
            $pageModel = PageModel::findById($pageModel);

        $layout = LayoutModel::findById($pageModel->layout);
        // Achtung moduleData enthält die ID, col, enable
        $moduleData = StringUtil::deserialize($layout->modules);

        $moduleInPage = PageLayoutListener::checkModules($pageModel,$database, [], [],$parameterBag);
        foreach ($moduleInPage as $modulInPage) {
            if (isset($modulInPage['moduleIds']))
                $moduleData[] = ['mod' => $modulInPage['moduleIds']];
            elseif(isset($modulInPage[0]))
                $moduleData[] = ['mod' => $modulInPage[0]];
        }
        $moduleInContent = PageLayoutListener::getModuleIdFromInsertTag($pageModel, $layout,$database);
        $moduleData[] = ['mod' => $moduleInContent['moduleIds']];

        return $moduleData;
    }

    public static function noScriptFallbackRenderScript(DataFromExternalMediaAndBar $dataFromExternalMediaAndBar): bool
    {
        if (
            isset($_SESSION)
            && isset($_SESSION['_sf2_attributes'])
            && isset($_SESSION['_sf2_attributes']['ncoi'])
            && isset($_SESSION['_sf2_attributes']['ncoi']['cookieIds'])
        ) {
            $cookieIds = $_SESSION['_sf2_attributes']['ncoi']['cookieIds'];
            foreach ($dataFromExternalMediaAndBar->getCookieIds() as $id) {
                if (in_array($id,$cookieIds)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getDataFromExternalMediaAndBar(
        string $iframeTypInHtml,
        DataFromExternalMediaAndBar $dataFromExternalMediaAndBar,
        ?CookieTool $cookieTool,
    ): DataFromExternalMediaAndBar {
        global $objPage;
        $provider = $objPage->rootTitle;
        $dataFromExternalMediaAndBar->setProvider($provider);
        $dataFromExternalMediaAndBar->setIFrameType(Blocker::getIFrameType($iframeTypInHtml));

        $dataFromExternalMediaAndBar
            ->addBlockedIFrames($cookieTool->getCookieToolsSelect());

        $dataFromExternalMediaAndBar->addCookieId($cookieTool->getId());
        $provider = $cookieTool->getCookieToolsProvider();
        if (!empty($provider)) {
            $dataFromExternalMediaAndBar->setProvider($provider);
        }
        $dataFromExternalMediaAndBar->setModId($cookieTool->getParent()->getSourceId());

        $privacyPolicyLink = $cookieTool->getCookieToolsPrivacyPolicyUrl()??'';
        $dataFromExternalMediaAndBar
            ->setPrivacyPolicyLink($privacyPolicyLink);

        $disclaimer = $cookieTool->getIFrameBlockedText();
        if (!empty($disclaimer))
                $dataFromExternalMediaAndBar->setDisclaimer($disclaimer);

        return $dataFromExternalMediaAndBar;
    }


    public static function getModIdByInsertTagInModule
    (
        Database $database,
        array $modIds,
        InsertTagParser $insertTagParser
    ): array {
        $moduleRepo = new ModuleRepository($database);
        $htmlInModules = $moduleRepo->findByIds($modIds);
        return self::getModuleIdsFromHtml($htmlInModules,$insertTagParser);
    }

    private static function getModuleIdsFromHtml($htmlInModules,InsertTagParser $insertTagParser): array
    {
        $ids = [];
        foreach ($htmlInModules as $html) {
            $id = self::getModuleIdFromHtml($html,$insertTagParser);
            if (!empty($id))
                $ids[] = $id;
        }
        return $ids;
    }
    private static function getModuleIdFromHtml($html,InsertTagParser $insertTagParser): float|int|string|null
    {
        if (is_array($html)) {
            foreach ($html as $item) {
                $id = self::getModuleIdFromOneHtml($item,$insertTagParser);
                if (!empty($id))
                    return $id;
            }
        } else {
            $id = self::getModuleIdFromOneHtml($html,$insertTagParser);
            if (!empty($id))
                return $id;
        }

        return null;
    }

    private static function getModuleIdFromOneHtml($html,InsertTagParser $insertTagParser): float|int|string|null
    {
        $position = strpos($html,'{{insert_module::');
        if ($position !== false) {
            $doc = new DOMDocument();
            $doc->loadHTML($html);
            $html = $doc->textContent;
            $html = str_replace('{{insert_module::','',$html);
            // all digitis from insertTag
            $id = $insertTagParser->replace($html);
            $id = preg_replace('/[^0-9]/', '', $id);
            if (is_numeric($id))
                return $id;
        }
        return null;
    }

    public static function getLevelUrl(string $url): string
    {
        $topLevelPosition = self::getTopLevel($url);
        if (!empty($topLevelPosition)) {
            $url = substr($url,0,$topLevelPosition);
            $urlArray = explode('.',$url);
            $url = $urlArray[array_key_last($urlArray)];
        }
        return $url;
    }

    private static function getTopLevel($url): ?int
    {
        $topLevelPosition = strpos($url,'.com');
        if ($topLevelPosition !== false)
            return $topLevelPosition;

        $topLevelPosition = strpos($url,'.net');
        if ($topLevelPosition !== false)
            return $topLevelPosition;

        $topLevelPosition = strpos($url,'.de');
        if ($topLevelPosition !== false)
            return $topLevelPosition;

        $topLevelPosition = strpos($url,'.at');
        if ($topLevelPosition !== false)
            return $topLevelPosition;

        return null;
    }

    /**
     * @param                      $iframeHTML
     * @param null                 $type
     *
     * @return CookieTool|null
     */
    public static function getType(
        $iframeHTML,
        $type = null
    ): ?string {

        if (empty($type))
            return self::getIFrameType($iframeHTML);
        return $type;
    }

    public static function getIFrameType($iframeHTML): string
    {

        $type = 'iframe';
        //Type des iFrames suchen damit danach in der Datenbank gesucht werden kann
        if (strpos($iframeHTML, 'youtube') !== false || strpos($iframeHTML, 'youtu.be') !== false) {
            $type = 'youtube';
        }elseif (strpos($iframeHTML, 'player.vimeo') !== false) {
            $type = 'vimeo';
        }elseif (strpos($iframeHTML, 'google.com/maps') || strpos($iframeHTML, 'maps.google') !== false) {
            $type = 'googleMaps';
        }

        return $type;
    }

    public static function getHtmlContainer(
        DataFromExternalMediaAndBar $dataFromExternalMediaAndBar,
        $loadStrings,
        $size,
        $html,
        InsertTagParser $insertTagParser,
        $iconPath = '',
        $isCustomGmap = false
    ): string {

        $privacyPolicyLink = $dataFromExternalMediaAndBar->getPrivacyPolicyLink();
        $provider = $dataFromExternalMediaAndBar->getProvider();

        //eigene Container immer mit ausgeben, damit über JavaScript .ncoi---hidden setzten kann.
        $htmlDisclaimer = '<div class="ncoi---blocked-disclaimer">';
        $htmlIcon = '';

        $disclaimerString = $dataFromExternalMediaAndBar->getDisclaimer();
        $disclaimerString = str_replace('{{provider}}','<a href="'.$privacyPolicyLink.'" target="_blank">'.$provider.'</a>',$disclaimerString);
        $htmlDisclaimer .= $disclaimerString;

        $id = uniqid();
        $iframeTypInHtml = $dataFromExternalMediaAndBar->getIFrameType();
        $blockClass = $dataFromExternalMediaAndBar->getIFrameType();
        $htmlReleaseAll = '';
        //$cookieId für JS um blocked Container ein. und auszublenden
        $cookieIds = $dataFromExternalMediaAndBar->getCookieIds();
        $class = 'ncoi---blocked ncoi---iframes ncoi---cookie-id-'.self::getCookieIdsAsString($cookieIds);
        switch($iframeTypInHtml) {
            case 'youtube':
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="' . $iconPath . 'youtube-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" type="checkbox" name="'.$blockClass.'" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'" data-cookie-ids="'.implode(',',$cookieIds).'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>YouTube '.$loadStrings['i_frame_always_load'].'</span></label>';
                break;
            case 'googleMaps':
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'map-marker-alt-solid.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'" data-cookie-ids="'.implode(',',$cookieIds).'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Google Maps '.$loadStrings['i_frame_always_load'].'</span></label>';
                break;
            case 'vimeo':
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'vimeo-v-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked--vimeo" data-block-class="'.$blockClass.'" data-cookie-ids="'.implode(',',$cookieIds).'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Vimeo '.$loadStrings['i_frame_always_load'].'</span></label>';
                break;
            case 'iframe':
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'" data-cookie-ids="'.implode(',',$cookieIds).'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>iFrames '.$loadStrings['i_frame_always_load'].'</span></label>';
                break;
            case 'script':
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="script" data-cookie-ids="'.implode(',',$cookieIds).'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Script '.$loadStrings['i_frame_always_load'].'</span></label>';
                break;
        }

        $htmlDisclaimer .= '</div>';

        $style = 'style="';
        if (!$isCustomGmap) {
            $height = $size['height'];
            if (!empty($height) && !self::hasUnit($height)) {
                $height .= 'px';
                $style .= 'style="height:'.$height.';';
            }
            $width = $size['width'];
            if (!empty($size['width']) && !self::hasUnit($width)) {
                $width .= 'px';
            }
            if (!empty($size['width']))
                $style .= ' width:'.$width.';';
        }
        $style .= '"';
        //Umschliedender Container damit Kinder zentiert werden könne
        $htmlContainer = '<div class="'.$class.'" '.$style.' >';
        $htmlContainerEnd = '</div>';

        //Container für alle Inhalte
        $htmlConsentBox = '<div class="ncoi---consent-box">';
        $htmlConsentBoxEnd = '</div>';

        $htmlForm = '<form method="post">';
        $htmlFormEnd = '</form>';
        //Damit JS das iFrame wieder laden kann
        $htmlConsentButton = '<div class="ncoi---blocked-link"><button type="submit" name="iframe" value="'.$iframeTypInHtml.'" class="ncoi---release">';

        $htmlConsentButtonEnd = '<span>' . $loadStrings['i_frame_load'].'</span></button></div>';
        $htmlInputCurrentPage = '<input class="ncoi---no-script--hidden" type="text" name="currentPage" value="'.$_SERVER['REDIRECT_URL'].'">';
        $htmlInputModID = '<input class="ncoi---no-script--hidden" type="text" name="data[modId]" value="'.$dataFromExternalMediaAndBar->getModId().'">';

        //Damit JS das iFrame wieder von base64 in ein HTML iFrame umwandel kann.
        $iframe = '';
        $html = $insertTagParser->replace($html);
        if (!$isCustomGmap) {
            $iframe = '<script type="text/template">' . ($html) . '</script>';
        }
        return $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlForm . $htmlConsentButton . $htmlIcon . $htmlConsentButtonEnd . $htmlInputCurrentPage .$htmlInputModID .$htmlFormEnd  .$htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;
    }

    /**
     * @throws Exception
     */
    public static function getIframeHTML(
        $iframeHTML,
        $requestStack,
        Database $database,
        ParameterBag $parameterBag,
        CookieToolRepository $cookieToolRepository,
        string $sourceId,
        InsertTagParser $insertTagParser
    )
    {
        $moduleData = Blocker::getModulData($requestStack,$database,$parameterBag);
        if (empty($moduleData))
            return $iframeHTML;

        $modIds = [];
        foreach ($moduleData as $moduleDatum) {
            $modIds[] = $moduleDatum['mod'];
        }
        $sourceIds = Blocker::getModIdByInsertTagInModule($database,$modIds,$insertTagParser);
        $sourceIds[] = $sourceId;

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $url = self::getUrl($iframeHTML);
        $url = Blocker::getLevelUrl($url);

        $iframeTypInHtml = Blocker::getIFrameType($iframeHTML);

        $cookieTools = $cookieToolRepository->findOneBySourceIdAndUrl($sourceIds, $url);
        $key = array_key_first($cookieTools);
        if ($key !== null) {
            $cookieTool = $cookieTools[array_key_first($cookieTools)];
        }
        if (empty($cookieTool)) {
            $cookieTools = $cookieToolRepository->findOneBySourceIdAndType($sourceIds, $iframeTypInHtml);
            $key = array_key_first($cookieTools);
            if ($key !== null) {
                $cookieTool = $cookieTools[array_key_first($cookieTools)];
            }
        }

        if (empty($cookieTool)) {
            global $objPage;
            $return = PageLayoutListener::checkModules(LayoutModel::findById($objPage->layout),$database, [], [],$parameterBag);
            $sourceIds[] = ['mod' => $return['moduleIds'][0]];
            $cookieTools = $cookieToolRepository->findOneBySourceIdAndUrl($sourceIds, $url);
            $key = array_key_first($cookieTools);
            if ($key !== null) {
                $cookieTool = $cookieTools[array_key_first($cookieTools)];
            }
            if (empty($cookieTool)) {
                $cookieTools = $cookieToolRepository->findOneBySourceIdAndType($sourceIds, $iframeTypInHtml);
                $key = array_key_first($cookieTools);
                if ($key !== null) {
                    $cookieTool = $cookieTools[array_key_first($cookieTools)];
                }
            }
        }

        if (empty($cookieTool)) {
            return $iframeHTML;
        }

        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $iframeTypInHtml,
            $dataFromExternalMediaAndBar,
            $cookieTool,
        );


        $isIFrameTypInDB = false;
        $blockedIFrames = $dataFromExternalMediaAndBar->getBlockedIFrames();
        if (in_array($iframeTypInHtml,$blockedIFrames) || empty($dataFromExternalMediaAndBar->getDisclaimer()))
            $isIFrameTypInDB = true;

        if (!$isIFrameTypInDB)
            return $iframeHTML;

        // alle icons liegen im gleich Ordner
        // root der bundle assets
        $iconPath = 'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR;
        $barRepo = new BarRepository($database);
        $blockTexts = $barRepo->loadBlockContainerTexts($dataFromExternalMediaAndBar->getModId());

        if (!empty($dataFromExternalMediaAndBar->getDisclaimer())) {

            $disclaimerString = $dataFromExternalMediaAndBar->getDisclaimer();

        } else {

            switch($iframeTypInHtml) {
                case 'youtube':
                case 'vimeo':
                    $disclaimerString = $blockTexts['i_frame_video'];
                    break;
                case 'googleMaps':
                    $disclaimerString = $blockTexts['i_frame_maps'];
                    break;
                case 'iframe':
                    $disclaimerString = $blockTexts['i_frame_i_frame'];
                    break;
                default:
                    $disclaimerString = 'Default disclaimer';
            }
        }
        $dataFromExternalMediaAndBar->setDisclaimer($disclaimerString);

        $size = [
            'height' => self::getHeight($iframeHTML),
            'width' => self::getWidth($iframeHTML)
        ];

        return [
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $size,
            $iconPath,
            $cookieTool
        ];
    }

    public static function isUserCookieDontAllowMedia(
        CookieTool $cookieTool
    )
    {
        if (
            isset($_SESSION)
            && isset($_SESSION['_sf2_attributes'])
            && isset($_SESSION['_sf2_attributes']['ncoi'])
            && isset($_SESSION['_sf2_attributes']['ncoi']['cookieIds'])
        ) {
            $cookieIds = $_SESSION['_sf2_attributes']['ncoi']['cookieIds'];
            return  $cookieIds == $cookieTool->getId();
        }
        return false;
    }

    private static function getHeight($iframeHTML): string
    {
        $position = self::getPosition($iframeHTML, 'max-height:');
        if (!empty($position)) {
            return '';
        }

        $position = self::getPosition($iframeHTML, 'height="');
        if (!empty($position)) {
            return self::getSizeFromAttribute($iframeHTML, $position);
        }

        $position = self::getPosition($iframeHTML, 'height:');
        if (!empty($position)) {
            return self::getSizeFromStyle($iframeHTML, $position);
        }

        return '';
    }

    private static function getWidth($iframeHTML): string
    {
        $position = self::getPosition($iframeHTML, 'max-width:');
        if (!empty($position)) {
            return '';
        }

        $position = self::getPosition($iframeHTML, 'width="');
        if (!empty($position)) {
            return self::getSizeFromAttribute($iframeHTML, $position);
        }

        $position = self::getPosition($iframeHTML, 'width:');
        if (!empty($position)) {
            return self::getSizeFromStyle($iframeHTML, $position);
        }

        return '';
    }

    private static function getSizeFromAttribute(
        string $iframeHTML,
        int $position
    )
    {
        return self::getSizeFrom($iframeHTML, $position, '"');
    }

    private static function getSizeFromStyle(
        string $iframeHTML,
        int $position
    )
    {
        return self::getSizeFrom($iframeHTML, $position, ';');
    }

    private static function getSizeFrom(
        string $iframeHTML,
        int $position,
        string $needle
    )
    {
        if (empty($position)) {
            return '';
        }
        $size = substr($iframeHTML, $position);
        $position = strpos($size, $needle);
        $size = substr($size, 0, $position);
        if (strpos($size, 'figure') !== false) {
            return '';
        }

        return $size;
    }

    public static function getPosition(
        $iframeHTML,
        $needle
    ): int
    {
        $heightPosition = strpos($iframeHTML, $needle);
        if ($heightPosition === false) {
            return 0;
        }

        return $heightPosition + strlen($needle);
    }

    private static function getUrl($html){

        $htmlUrlPart = substr($html,strpos($html,'src="'));
        $htmlUrlPart = str_replace('src="','',$htmlUrlPart);
        $htmlUrlPart = str_replace('www.','',$htmlUrlPart);
        $htmlUrlPart = substr($htmlUrlPart,0,strpos($htmlUrlPart,'"'));

        $urlArray = explode('/',$htmlUrlPart);
        foreach ($urlArray as $url) {
            if (strpos($url,'.'))
                return $url;
        }

        return '';
    }

    public static function hasUnit($html)
    {
        $units = [
            'px',
            '%',
            'em',
            'rem',
            'vw',
            'vh',
            'vmin',
            'vmax',
            'ex',
            'pt',
            'pc',
            'in',
            'cm',
            'mm',
        ];
        foreach ($units as $unit) {
            if (str_contains($html, $unit))
                return true;

        }
        return false;
    }

    /**
     * @param array $cookieIds
     * @return string
     */
    private static function getCookieIdsAsString(array $cookieIds): string
    {
        if (count($cookieIds) == 1) {
            $cookieIdsAsString = $cookieIds[0];
        } else {
            $cookieIdsAsString = implode(' ncoi---cookie-id-', $cookieIds);
        }
        return $cookieIdsAsString;
    }
}