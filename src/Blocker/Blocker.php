<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\Database;
use Contao\InsertTags;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use DOMDocument;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\ModuleRepository;
use Netzhirsch\CookieOptInBundle\Repository\ToolRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class Blocker
{

    public static function getModulData(RequestStack $requestStack,Database $database) {
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

        $moduleInPage = PageLayoutListener::checkModules($pageModel,$database, [], []);
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

    public static function noScriptFallbackRenderScript(DataFromExternalMediaAndBar $dataFromExternalMediaAndBar){
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

    /**
     * @param DataFromExternalMediaAndBar $dataFromExternalMediaAndBar
     * @param Database $database
     * @param array $externalMediaCookiesInDB
     * @param $moduleData
     * @return DataFromExternalMediaAndBar
     */
    public static function getDataFromExternalMediaAndBar(
        DataFromExternalMediaAndBar $dataFromExternalMediaAndBar,
        Database $database,
        $externalMediaCookiesInDB,
        $moduleData
    )
    {
        global $objPage;
        $provider = $objPage->rootTitle;
        $dataFromExternalMediaAndBar->setProvider($provider);

        $barRepo = new BarRepository($database);
        $cookieBars = $barRepo->findAll();
        $isModuleIdInLayout = false;
        $modIds = [];
        foreach ($cookieBars as $cookieBar) {
            foreach ($moduleData as $moduleId) {
                if ($cookieBar['pid'] == $moduleId['mod']) {
                    foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                        if ($cookieBar['pid'] == $externalMediaCookieInDB['pid']) {
                            $isModuleIdInLayout = true;
                            $dataFromExternalMediaAndBar
                                ->addBlockedIFrames($externalMediaCookieInDB['cookieToolsSelect']);

                            $dataFromExternalMediaAndBar->addCookieId($externalMediaCookieInDB['id']);

                            if (!empty($externalMediaCookieInDB['cookieToolsProvider'])) {
                                $dataFromExternalMediaAndBar
                                    ->setProvider($externalMediaCookieInDB['cookieToolsProvider']);
                            }
                            $dataFromExternalMediaAndBar->setModId($cookieBar['pid']);

                            $privacyPolicyLink = '';
                            if (!empty($externalMediaCookieInDB['cookieToolsPrivacyPolicyUrl'])) {
                                $privacyPolicyLink = $externalMediaCookieInDB['cookieToolsPrivacyPolicyUrl'];

                            }
                            elseif (!empty(PageModel::findById($cookieBar['privacyPolicy']))) {
                                $privacyPolicyLink = PageModel::findById($cookieBar['privacyPolicy']);
                                $privacyPolicyLink = $privacyPolicyLink->getFrontendUrl();
                            }
                            $dataFromExternalMediaAndBar
                                ->setPrivacyPolicyLink($privacyPolicyLink);

                            $disclaimer = $externalMediaCookieInDB['i_frame_blocked_text'];
                            if (!empty($disclaimer))
                                $dataFromExternalMediaAndBar->setDisclaimer($disclaimer);
                        }
                    }
                }
                if (!empty($moduleId['mod']))
                    $modIds[] = $moduleId['mod'];
            }
        }

        if (!$isModuleIdInLayout) {

            self::setModIdByInsertTagInModule($database,$modIds,$barRepo,$dataFromExternalMediaAndBar);
        }
        foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
            $dataFromExternalMediaAndBar->addCookieId($externalMediaCookieInDB['id']);
        }

        return $dataFromExternalMediaAndBar;
    }


    private static function setModIdByInsertTagInModule
    (
        Database $database,
        array $modIds,
        BarRepository $barRepo,
        DataFromExternalMediaAndBar $dataFromExternalMediaAndBar
    ) {
        $moduleRepo = new ModuleRepository($database);
        $htmlInModules = $moduleRepo->findByIds($modIds);
        $ids = self::getModuleIdsFromHtml($htmlInModules);
        if (!empty($ids)) {
            $barModule = $barRepo->findByIds($ids);
            if (!empty($barModule) && !empty($barModule['pid'])) {
                $dataFromExternalMediaAndBar->setModId($barModule['pid']);
            }
        }
    }

    private static function getModuleIdsFromHtml($htmlInModules)
    {
        $ids = [];
        foreach ($htmlInModules as $html) {
            $id = self::getModuleIdFromHtml($html);
            if (!empty($id))
                $ids[] = $id;
        }
        return $ids;
    }
    private static function getModuleIdFromHtml($html)
    {
        if (is_array($html)) {
            foreach ($html as $item) {
                $id = self::getModuleIdFromOneHtml($item);
                if (!empty($id))
                    return $id;
            }
        } else {
            $id = self::getModuleIdFromOneHtml($html);
            if (!empty($id))
                return $id;
        }

        return null;
    }

    private static function getModuleIdFromOneHtml($html){
        $position = strpos($html,'{{insert_module::');
        if ($position !== false) {
            $doc = new DOMDocument();
            $doc->loadHTML($html);
            $html = $doc->textContent;
            $insertTags = explode('insert_module::',$html);
            foreach ($insertTags as $insertTag) {
                $id = str_replace('{{','',$insertTag);
                $id = str_replace('}}','',$id);
                $id = trim($id);
                if (is_numeric($id))
                    return $id;
            }
        }
        return null;
    }

    /**
     * @param Database $database
     * @param $url
     * @return mixed[]
     */
    public static function getExternalMediaByUrl(Database $database, $url) {
        $toolRepo = new ToolRepository($database);
        $topLevelPosition = self::getTopLevel($url);
        if (!empty($topLevelPosition)) {
            $url = substr($url,0,$topLevelPosition);
            $urlArray = explode('.',$url);
            $url = $urlArray[array_key_last($urlArray)];
        }
        return $toolRepo->findByUrl($url);
    }

    private static function getTopLevel($url){
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
     * @param $iframeHTML
     * @param Database $database
     * @param null $type
     * @return array
     */
    public static function getExternalMediaByType($iframeHTML,Database $database,$type = null){

        $toolRepo = new ToolRepository($database);

        if (empty($type))
            $type = self::getIFrameType($iframeHTML);
        if (empty($type))
            return [];

        return $toolRepo->findByType($type);
    }

    public static function getIFrameType($iframeHTML){

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
        $iconPath = '',
        $isCustomGmap = false,
        $isC4GMap = false
    ) {

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
        if ($isC4GMap) {
            $style .= 'height:'.$size['height'].'; width:'.$size['width'].';';
        }
        $style .= '"';
        //Umschliedender Container damit Kinder zentiert werden könne
        $htmlContainer = '<div class="'.$class.'" '.$style.' >';
        $htmlContainerEnd = '</div>';

        //Container für alle Inhalte
        $htmlConsentBox = '<div class="ncoi---consent-box">';
        $htmlConsentBoxEnd = '</div>';

        $htmlForm = '<form action="/cookie/allowed/iframe" method="post">';
        $htmlFormEnd = '</form>';
        //Damit JS das iFrame wieder laden kann
        $htmlConsentButton = '<div class="ncoi---blocked-link"><button type="submit" name="iframe" value="'.$iframeTypInHtml.'" class="ncoi---release">';

        $htmlConsentButtonEnd = '<span>' . $loadStrings['i_frame_load'].'</span></button></div>';
        $htmlInputCurrentPage = '<input class="ncoi---no-script--hidden" type="text" name="currentPage" value="'.$_SERVER['REDIRECT_URL'].'">';
        $htmlInputModID = '<input class="ncoi---no-script--hidden" type="text" name="data[modId]" value="'.$dataFromExternalMediaAndBar->getModId().'">';

        //Damit JS das iFrame wieder von base64 in ein HTML iFrame umwandel kann.
        $iframe = '';
        $html = InsertTags::replaceInsertTags($html);
        if (!$isCustomGmap) {
            $iframe = '<script type="text/template">' . ($html) . '</script>';
        }
        return $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlForm . $htmlConsentButton . $htmlIcon . $htmlConsentButtonEnd . $htmlInputCurrentPage .$htmlInputModID .$htmlFormEnd  .$htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;
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
            if (strpos($html,$unit) !== false)
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