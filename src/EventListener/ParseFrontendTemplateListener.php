<?php
namespace Netzhirsch\CookieOptInBundle\EventListener;

use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Statement;
use DOMDocument;
use DOMElement;
use Netzhirsch\CookieOptInBundle\Repository\ToolRepository;
use Netzhirsch\CookieOptInBundle\Struct\DataFromExternalMediaAndBar;
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
                    return $this->iframe($buffer);
                }
            }
            if (strpos($template, 'google') !== false) {
                return $this->analyticsTemplate($buffer,'googleAnalytics');
            } elseif (strpos($template, 'piwik') !== false || strpos($template, 'matomo') !== false) {
                return $this->analyticsTemplate($buffer,'matomo');
            }
            if ($template == 'ce_html' && strpos($buffer, '<script') !== false) {
                return $this->script($buffer);
            }
        }

        // nichts ändern
        return $buffer;
    }

    /**
     * @param $buffer
     * @return string
     * @throws DBALException|Exception
     */
    private function iframe($buffer){

        $conn = $this->getConnection();
        $requestStack = $this->getRequestStack();
        if (empty($requestStack))
            return $buffer;
        /**
         * IFrames von anderen HTML Tags trennen.
         * IFrames encoden und in Container div einbetten.
         * Andere HTML Tags einfach ans Return anhängen.
         */
        $htmlArray = explode('<iframe',$buffer);
        $return = '';
        foreach ($htmlArray as $html) {
            if (strpos($html,'</iframe') !== false) {
                $iframeArray = strpos($html,'</iframe>');
                if ($iframeArray !== false) {
                    $iframe = substr($html,0,$iframeArray);
                    $iframeHTML = '<iframe'.$iframe;
                    $return .= $this->getIframeHTML($iframeHTML,$requestStack,$conn);
                }
                $return .= substr($html,$iframeArray,strlen($html));;
            } else {
                $return .= $html;
            }
        }
        return $return;
    }

    /**
     * @param $iframeHTML
     * @param $requestStack
     * @param $conn
     * @return string
     * @throws DBALException
     * @throws Exception
     */
    private function getIframeHTML($iframeHTML,$requestStack,$conn)
    {
        // Speicher blockierte IFrame Typen
        $blockedIFrames = [];

        //Frontendvariablen diese werden an das Template übergeben
        $iframeTypInHtml = $this->getIFrameType($iframeHTML);

        $url = $this->getUrl($iframeHTML);
        $toolRepo = new ToolRepository($conn);
        $externalMediaCookiesInDB = $toolRepo->findByUrl($url);

        $moduleData = $this->getModulData($requestStack);
        if (empty($moduleData))
            return $iframeHTML;

        $dataFromExternalMediaAndBar = $this->getDataFromExternalMediaAndBar($conn,$url,$moduleData,$iframeHTML);
        // Wenn iFrame nicht im Backend, kann nur das iFrame zurückgegeben werden.
        $isIFrameTypInDB = false;
        if (in_array($iframeTypInHtml,$blockedIFrames) || empty($dataFromExternalMediaAndBar->getDisclaimer()))
            $isIFrameTypInDB = true;

        if (!$isIFrameTypInDB)
            return $iframeHTML;

        // alle icons liegen im gleich Ordner
        // root der bundle assets
        $iconPath = 'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR;

        $blockTexts = $this->loadBlockContainerTexts($dataFromExternalMediaAndBar->getModId());

        if (!empty($dataFromExternalMediaAndBar->getDisclaimer())) {

            $disclaimerString = $dataFromExternalMediaAndBar->getDisclaimer();

        } else {

            $disclaimerString = '';
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
            }
        }

        // Abmessungen des Block Container, damit es die gleiche Göße wie das iFrame hat.
        $heightPosition = strpos($iframeHTML, 'height="')+strlen('height="');
        $height = substr($iframeHTML, $heightPosition);
        $heightPosition = strpos($height, '"');
        $height = substr($height, 0,$heightPosition);

        $widthPosition = strpos($iframeHTML, 'width="')+strlen('width="');
        $width = substr($iframeHTML, $widthPosition);
        $widthPosition = strpos($width, '"');
        $width = substr($width, 0,$widthPosition);

        $newBuffer = $this->getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $disclaimerString,
            $height,
            $width,
            $iframeHTML,
            $iconPath,
        );

        $isUserCookieDontAllowMedia = false;
        if (
            isset($_SESSION)
            && isset($_SESSION['_sf2_attributes'])
            && isset($_SESSION['_sf2_attributes']['ncoi'])
            && isset($_SESSION['_sf2_attributes']['ncoi']['cookieIds'])
            && !empty($externalMediaCookiesInDB)
        ) {
            $cookieIds = $_SESSION['_sf2_attributes']['ncoi']['cookieIds'];
            foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                if (isset($externalMediaCookieInDB['id']) && in_array($externalMediaCookieInDB['id'],$cookieIds)) {
                    $isUserCookieDontAllowMedia = true;
                }
            }
        }
        //User möchte das iFrame sehen, aber vielleicht auch über JS wieder blocken
        if ($isUserCookieDontAllowMedia) {
            return $iframeHTML;
        } else {
            return $newBuffer;
        }
    }

    /**
     * @param $buffer
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function script($buffer) {

        $conn = $this->getConnection();
        $requestStack = $this->getRequestStack();
        if (empty($requestStack))
            return $buffer;

        /**
         * Scripts von anderen HTML Tags trennen.
         * Scripts encoden und in Container div einbetten.
         * Andere HTML Tags einfach ans Return anhängen.
         */

        $doc = new DOMDocument();
        $doc->loadHTML($buffer);
        $htmlArray = $doc->getElementsByTagName('script');
        $newBuffer = '';
        foreach ($htmlArray as $html) {
            $newBuffer .= $this->getScriptHTML($html,$requestStack,$conn,$buffer);
        }
        if (empty($newBuffer))
            return $buffer;
        return $newBuffer;
    }

    /**
     * @param DOMElement $DOMElement
     * @param RequestStack $requestStack
     * @param Connection $conn
     * @param $buffer
     * @return null|string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getScriptHTML(DOMElement $DOMElement, RequestStack $requestStack, Connection $conn,$buffer){

        $moduleData = $this->getModulData($requestStack);
        if (empty($moduleData))
            return null;

        $dataFromExternalMediaAndBar = $this->getDataFromExternalMediaAndBar(
            $conn,$DOMElement->getAttribute('src'),$moduleData
        );

        $dataFromExternalMediaAndBar->setIFrameType('script');
        $blockText = $this->loadBlockContainerTexts($dataFromExternalMediaAndBar->getModId());

        if ($this->isAllowed($dataFromExternalMediaAndBar))
            return $buffer;

        return $this->getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockText,
            $dataFromExternalMediaAndBar->getDisclaimer(),
            $DOMElement->getAttribute('height'),
            $DOMElement->getAttribute('width'),
            $buffer
        );
    }

    private function isAllowed(DataFromExternalMediaAndBar $dataFromExternalMediaAndBar){
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


    private function getHtmlContainer(
        DataFromExternalMediaAndBar $dataFromExternalMediaAndBar,
        $blockTexts,
        $disclaimerString,
        $height,
        $width,
        $html,
        $iconPath = ''
    ) {

        $cookieIds = $dataFromExternalMediaAndBar->getCookieIds();
        $privacyPolicyLink = $dataFromExternalMediaAndBar->getPrivacyPolicyLink();
        $provider = $dataFromExternalMediaAndBar->getProvider();

        //eigene Container immer mit ausgeben, damit über JavaScript .ncoi---hidden setzten kann.
        $htmlDisclaimer = '<div class="ncoi---blocked-disclaimer">';
        $htmlIcon = '';

        $disclaimerString = str_replace('{{provider}}','<a href="'.$privacyPolicyLink.'" target="_blank">'.$provider.'</a>',$disclaimerString);
        $htmlDisclaimer .= $disclaimerString;

        $id = uniqid();
        $iframeTypInHtml = $dataFromExternalMediaAndBar->getIFrameType();
        $blockClass = $dataFromExternalMediaAndBar->getIFrameType();
        switch($iframeTypInHtml) {
            case 'youtube':
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="' . $iconPath . 'youtube-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" type="checkbox" name="'.$blockClass.'" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>YouTube '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'googleMaps':
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'map-marker-alt-solid.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Google Maps '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'vimeo':
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'vimeo-v-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked--vimeo" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Vimeo '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'iframe':
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>iFrames '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'script':
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="script"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Script '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
        }

        $htmlDisclaimer .= '</div>';


        //$blockclass im JS um blocked Container ein. und auszublenden
        $class = 'ncoi---blocked ncoi---iframes ncoi---'.$blockClass;
        if (!empty($cookieIds)) {
            if (count($cookieIds) == 1) {
                $class .= ' ncoi---cookie-id-'.$cookieIds[0];
            } else {
                $class .= implode(' ncoi---cookie-id-',$cookieIds);
            }
        }


        if(!$this->hasUnit($width))
            $width .= 'px';
        if(!$this->hasUnit($height))
            $height .= 'px';

        //Umschliedender Container damit Kinder zentiert werden könne
        $htmlContainer = '<div class="'.$class.'" style="height:' . $height . '; width:' . $width . '" >';
        $htmlContainerEnd = '</div>';

        //Container für alle Inhalte
        $htmlConsentBox = '<div class="ncoi---consent-box">';
        $htmlConsentBoxEnd = '</div>';

        $htmlForm = '<form action="/cookie/allowed/iframe" method="post">';
        $htmlFormEnd = '</form>';
        //Damit JS das iFrame wieder laden kann
        $htmlConsentButton = '<div class="ncoi---blocked-link"><button type="submit" name="iframe" value="'.$iframeTypInHtml.'" class="ncoi---release">';

        $htmlConsentButtonEnd = '<span>' . $blockTexts['i_frame_load'].'</span></button></div>';
        $htmlInputCurrentPage = '<input class="ncoi---no-script--hidden" type="text" name="currentPage" value="'.$_SERVER['REDIRECT_URL'].'">';
        $htmlInputModID = '<input class="ncoi---no-script--hidden" type="text" name="data[modId]" value="'.$dataFromExternalMediaAndBar->getModId().'">';

        //Damit JS das iFrame wieder von base64 in ein HTML iFrame umwandel kann.
        $iframe = '<script type="text/template">' . base64_encode($html) . '</script>';

        return $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlForm . $htmlConsentButton . $htmlIcon . $htmlConsentButtonEnd . $htmlInputCurrentPage .$htmlInputModID .$htmlFormEnd  .$htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;
    }
    /**
     * @param Connection $conn
     * @param $url
     * @param $moduleData
     * @param string $iframeHTML
     * @return DataFromExternalMediaAndBar
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDataFromExternalMediaAndBar(Connection $conn,$url,$moduleData,$iframeHTML = '')
    {
        global $objPage;
        $provider = $objPage->rootTitle;
        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar($provider);
        $externalMediaCookiesInDB = $this->getExternalMediaByUrl($conn, $url);
        if (empty($externalMediaCookiesInDB) && !empty($iframeHTML)) {
            $externalMediaCookiesInDB = $this->getExternalMediaByType($iframeHTML,$conn);
            $dataFromExternalMediaAndBar->setIFrameType($this->getIFrameType($iframeHTML));
        }

        $barRepo = new BarRepository($conn);
        $cookieBars = $barRepo->findAll();
        foreach ($cookieBars as $cookieBar) {
            foreach ($moduleData as $moduleId) {
                if ($cookieBar['pid'] == $moduleId['mod']) {
                    foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                        if ($cookieBar['pid'] == $externalMediaCookieInDB['pid']) {
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
            }
        }

        return $dataFromExternalMediaAndBar;
    }

    /**
     * @param Connection $conn
     * @param $url
     * @return mixed[]
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getExternalMediaByUrl(Connection $conn, $url) {
        $toolRepo = new ToolRepository($conn);
        $topLevelPosition = strpos($url,'.com');
        $url = substr($url,0,$topLevelPosition);
        $urlArray = explode('.',$url);
        $secondLevel = $urlArray[array_key_last($urlArray)];
        return $toolRepo->findByUrl($secondLevel);
    }


    private function getIFrameType($iframeHTML){

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

    /**
     * @param $iframeHTML
     * @param Connection $conn
     * @return array|mixed[]|void
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getExternalMediaByType($iframeHTML,Connection $conn){

        $toolRepo = new ToolRepository($conn);

        $type = $this->getIFrameType($iframeHTML);
        if (empty($type))
            return [];

        return $toolRepo->findByType($this->getIFrameType($iframeHTML));
    }

    private function analyticsTemplate($buffer,$analyticsType) {
        //class hinzufügen damit die in JS genutzt werden kann
        $buffer = str_replace('<script','<script class="analytics-decoded-'.$analyticsType.'"',$buffer);
        return '<script id="analytics-encoded-'.$analyticsType.'"><!-- '.base64_encode($buffer).' --></script>';
    }

    private function getModulData(RequestStack $requestStack) {
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
        $layout = LayoutModel::findById($pageModel->layout);
        // Achtung moduleData enthält die ID, col, enable
        $moduleData = StringUtil::deserialize($layout->modules);

        $moduleInPage = PageLayoutListener::checkModules($pageModel, [], []);
        foreach ($moduleInPage as $modulInPage) {
            if (isset($modulInPage['moduleIds']))
                $moduleData[] = ['mod' => $modulInPage['moduleIds']];
            else
                $moduleData[] = ['mod' => $modulInPage[0]];
        }
        $moduleInContent = PageLayoutListener::getModuleIdFromInsertTag($pageModel, $layout);
        $moduleData[] = ['mod' => $moduleInContent['moduleIds']];

        return $moduleData;
    }

    private function getUrl($html){

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

    private function getContainer() {
        return System::getContainer();
    }


    private function loadBlockContainerTexts($modId) {

        $container = System::getContainer();
        $conn = $container->get('database_connection');

        $sql
            = "SELECT i_frame_video,i_frame_maps,i_frame_i_frame,i_frame_always_load,i_frame_load 
                FROM tl_ncoi_cookie WHERE pid = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->execute();

        return $stmt->fetch();
    }

    private function hasUnit($html)
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
}
