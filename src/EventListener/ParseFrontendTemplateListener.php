<?php
namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Symfony\Component\HttpFoundation\RequestStack;

class ParseFrontendTemplateListener
{
    /**
     * @param $buffer
     * @param $template
     * @return string
     * @throws DBALException
     */
    public function onParseFrontendTemplate($buffer, $template)
    {
        //iFrame als HTML Element eingebunden
        if (
            $template == 'ce_html' && strpos($buffer, '<iframe') !== false
            || $template == 'ce_youtube' && strpos($buffer, '<iframe') !== false
            || $template == 'ce_vimeo' && strpos($buffer, '<iframe') !== false
        ) {
            return $this->iframe($buffer);
        } elseif ($template == 'analytics_google' && !empty($buffer)
            || $template == 'analytics_piwik' && !empty($buffer)) {
            return $this->analyticsTemplate($buffer);
        } elseif ($template == 'mod_matomo_TrackingTagAsynchron') {
            return $this->matomoTrackingTagTemplate($buffer);
        }

        // nichts ändern
        return $buffer;
    }

    /**
     * @param $buffer
     * @return string
     * @throws DBALException
     */
    private function iframe($buffer){

        /**
         * Coantiner und RequestStack einmalig für die Schleife holen
         */
        $container = System::getContainer();
        $conn = $container->get('database_connection');
        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');

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
     */
    private function getIframeHTML($iframeHTML,$requestStack,$conn)
    {
        // Speicher blockierte IFrame Typen
        $blockedIFrames = [];

        //Frontendvariablen diese werden an das Template übergeben
        $iframeTypInHtml = 'iframe';
        $privacyPolicyLink = '';
        $modId = null;
        $cookieIds = [];
        $externalMediaCookiesInDB = null;

        //Type des iFrames suchen damit danach in der Datenbank gesucht werden kann
        if (strpos($iframeHTML, 'youtube') !== false || strpos($iframeHTML, 'youtu.be') !== false) {
            $iframeTypInHtml = 'youtube';
        }elseif (strpos($iframeHTML, 'player.vimeo') !== false) {
            $iframeTypInHtml = 'vimeo';
        }elseif (strpos($iframeHTML, 'google.com/maps') || strpos($iframeHTML, 'maps.google') !== false) {
            $iframeTypInHtml = 'googleMaps';
        }

        //Suche nach dem iFrame.
        $sql = "SELECT id,pid,cookieToolsSelect FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, 'cookieTools');
        $stmt->bindValue(2, $iframeTypInHtml);
        $stmt->execute();
        $externalMediaCookiesInDB = $stmt->fetchAll();

        if (!empty($requestStack)) {

            /**
             * Attribute aus dem Request für das PageModel suchen.
             */
            $attributes = $requestStack->getCurrentRequest()->attributes;
            if (!empty($attributes)) {

                /**
                 * Aus dem PageModel die ModulIds finden, damit diese mit dem PID der CookieBar verglichen werden kann.
                 * Stimmt die PID ,so ist diese Cookiebar für dieses Modul gedacht.
                 */
                /** @var PageModel $pageModel */
                $pageModel = $attributes->get('pageModel');
                // Contao 4.4
                if (empty($pageModel))
                    $pageModel = $GLOBALS['objPage'];

                $return = PageLayoutListener::checkModules($pageModel, [], []);
                if (empty($return['moduleIds']))
                    $return = PageLayoutListener::getModuleIdFromInsertTag($pageModel);
                // Achtung moduleData enthält nur die ID
                $moduleData[] = ['mod' => $return['moduleIds']];
                if (empty($moduleData)) {
                    $layout = LayoutModel::findById($pageModel->layout);
                    // Achtung moduleData enthält die ID, col, enable
                    $moduleData = StringUtil::deserialize($layout->modules);
                }
                // Alle Cookiebars finden um über die ModuleIds die richtig zu finden.
                $sql = "SELECT id,pid,privacyPolicy FROM tl_ncoi_cookie ";
                /** @var Statement $stmt */
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $cookieBars = $stmt->fetchAll();

                foreach ($cookieBars as $cookieBar) {
                    foreach ($moduleData as $moduleId) {
                        if ($cookieBar['pid'] == $moduleId['mod']) {
                            foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                                if ($cookieBar['pid'] == $externalMediaCookieInDB['pid']) {
                                    $blockedIFrames[] = $externalMediaCookieInDB['cookieToolsSelect'];
                                    $cookieIds[] = $externalMediaCookieInDB['id'];
                                    $modId = $cookieBar['pid'];

                                    if (!empty(PageModel::findById($cookieBar['privacyPolicy']))) {
                                        $privacyPolicyLink = PageModel::findById($cookieBar['privacyPolicy']);
                                        $privacyPolicyLink = $privacyPolicyLink->getFrontendUrl();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //eigene Container immer mit ausgeben, damit über JavaScript .ncoi---hidden setzten kann.
        $htmlDisclaimer = '<div class="ncoi---blocked-disclaimer">';
        $htmlIcon = '';
        // alle icons liegen im gleich Ordner
        // root der bundle assets
        $iconPath = 'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR;

        /**
         * iFrame spezifisches HTML
         */
        $blockClass = 'ncoi---'.$iframeTypInHtml;
        $blockTexts = $this->loadBlockContainerTexts($modId);
        $id = uniqid();
        switch($iframeTypInHtml) {
            case 'youtube':
                $htmlDisclaimer .=  $blockTexts['i_frame_video'].' <a href="https://policies.google.com/privacy" target="_blank">YouTube</a>.';
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="' . $iconPath . 'youtube-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" type="checkbox" name="'.$blockClass.'" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Youtube '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'googleMaps':
                $htmlDisclaimer .= $blockTexts['i_frame_maps'].' <a href="https://policies.google.com/privacy" target="_blank">Google LLC</a>.';
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'map-marker-alt-solid.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Google Maps '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'vimeo':
                $htmlDisclaimer .= $blockTexts['i_frame_video'].' <a href="https://vimeo.com/privacy" target="_blank">Vimeo</a>.';
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'vimeo-v-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked--vimeo" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Vimeo '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
            case 'iframe':
            default:
                global $objPage;
                $htmlDisclaimer .= $blockTexts['i_frame_i_frame'].' <a href="/'.$privacyPolicyLink.'" target="_blank">'.$objPage->rootTitle.'</a>.';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>iFrames '.$blockTexts['i_frame_always_load'].'</span></label>';
                break;
        }
        $htmlDisclaimer .= '</div>';

        // Wenn iFrame nicht im Backend, kann nur das iFrame zurückgegeben werden.
        $isIFrameTypInDB = false;
        if (in_array($iframeTypInHtml,$blockedIFrames))
            $isIFrameTypInDB = true;

        if (!$isIFrameTypInDB)
            return $iframeHTML;

        //$blockclass im JS um blocked Container ein. und auszublenden
        $class = 'ncoi---blocked ncoi---iframes '.$blockClass;
        if (!empty($cookieIds)) {
            if (count($cookieIds) == 1) {
                $class .= ' ncoi---cookie-id-'.$cookieIds[0];
            } else {
                $class .= implode(' ncoi---cookie-id-',$cookieIds);
            }
        }

        // Abmessungen des Block Container, damit es die gleiche Göße wie das iFrame hat.
        $height = substr($iframeHTML, strpos($iframeHTML, 'height'), 11);
        $height = substr($height, 8, 3);

        $width = substr($iframeHTML, strpos($iframeHTML, 'width'), 11);
        $width = substr($width, 7, 3);

        //Umschliedender Container damit Kinder zentiert werden könne
        $htmlContainer = '<div class="'.$class.'" style="height:' . $height . 'px; width:' . $width . 'px" >';
        $htmlContainerEnd = '</div>';

        //Container für alle Inhalte
        $htmlConsentBox = '<div class="ncoi---consent-box">';
        $htmlConsentBoxEnd = '</div>';

        $htmlForm = '<form action="/cookie/allowed/iframe" method="post">';
        $htmlFormEnd = '</form>';
        //Damit JS das iFrame wieder laden kann
        $htmlConsentButton = '<div class="ncoi---blocked-link">
<button type="submit" name="iframe" value="'.$iframeTypInHtml.'" class="ncoi---release">';

        $htmlConsentButtonEnd = '<span>' . $blockTexts['i_frame_load'].'</span></button></div>';
        $htmlInputCurrentPage = '<input class="ncoi---no-script--hidden" type="text" name="currentPage" value="'.$_SERVER['REDIRECT_URL'].'">';
        $htmlInputModID = '<input class="ncoi---no-script--hidden" type="text" name="data[modId]" value="'.$modId.'">';

        //Damit JS das iFrame wieder von base64 in ein HTML iFrame umwandel kann.
        $iframe = '<script type="text/template">' . base64_encode($iframeHTML) . '</script>';

        $newBuffer = $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlForm . $htmlConsentButton . $htmlIcon . $htmlConsentButtonEnd . $htmlInputCurrentPage .$htmlInputModID .$htmlFormEnd  .$htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;

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
    private function analyticsTemplate($buffer) {
        // cookie tool select finden
        if (strpos($buffer, 'googletagmanager.com') !== false) {
            $analyticsType = 'googleAnalytics';
        } else {
            $analyticsType = 'matomo';
        }
        //class hinzufügen damit die in JS genutzt werden kann
        $buffer = str_replace('<script','<script class="analytics-decoded-'.$analyticsType.'"',$buffer);
        return '<script id="analytics-encoded-'.$analyticsType.'"><!-- '.base64_encode($buffer).' --></script>';
    }

    private function matomoTrackingTagTemplate($buffer) {
        $buffer = str_replace('<script','<script class="analytics-decoded-matomo-tracking-tag"',$buffer);
        return '<script id="analytics-encoded-matomo-tracking-tag"><!-- '.base64_encode($buffer).' --></script>';
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
}
