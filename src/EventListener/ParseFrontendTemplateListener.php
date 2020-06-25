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
        }
        // nichts ändern
        return $buffer;
    }

    /**
     * @param $buffer
     * @return string
     */
    private function iframe($buffer){

        //Datenbank und User Cookie
        $container = System::getContainer();
        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');

        $htmlArray = explode('<iframe',$buffer);
        $return = '';
        foreach ($htmlArray as $html) {
            if (strpos($html,'</iframe') !== false) {
                $iframeArray = strpos($html,'</iframe>');
                if ($iframeArray !== false) {
                    $iframe = substr($html,0,$iframeArray);
                    $iframeHTML = '<iframe'.$iframe;
                    $return .= $this->getIframeHTML($iframeHTML,$requestStack,$container);
                }
                $return .= substr($html,$iframeArray,strlen($html));;
            } else {
                $return .= $html;
            }
        }
        return $return;
    }

    private function getIframeHTML($iframeHTML,$requestStack,$container)
    {
        // Block Entscheidungsvariablen
        $blockedIFrames = [];

        //Frontendvariablen
        $iframeTypInHtml = 'iframe';
        $privacyPolicyLink = '';
        $modId = null;
        $cookieIds = [];

        //Wenn null werden alle iFrames angezeigt.
        if (!empty($requestStack)) {

            //Type des iFrames suchen damit danach in der Datenbank gesucht werden kann
            if (strpos($iframeHTML, 'youtube') !== false || strpos($iframeHTML, 'youtu.be') !== false) {
                $iframeTypInHtml = 'youtube';
            }elseif (strpos($iframeHTML, 'player.vimeo') !== false) {
                $iframeTypInHtml = 'vimeo';
            }elseif (strpos($iframeHTML, 'google.com/maps') || strpos($iframeHTML, 'maps.google') !== false) {
                $iframeTypInHtml = 'googleMaps';
            }

            //Suche nach dem iFrame.
            $conn = $container->get('database_connection');
            $sql = "SELECT id,pid,cookieToolsSelect FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";
            /** @var Statement $stmt */
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, 'cookieTools');
            $stmt->bindValue(2, $iframeTypInHtml);
            $stmt->execute();
            $externalMediaCookiesInDB = $stmt->fetchAll();

            //Feststellen ob iFrame laut Backend geblocked werden sollen
            // und Datenschutz url finden
            $attributes = $requestStack->getCurrentRequest()->attributes;
            if (!empty($attributes)) {
                /** @var PageModel $pageModel */
                $pageModel = $attributes->get('pageModel');
                // Contao 4.4
                if (empty($pageModel))
                    $pageModel = $GLOBALS['objPage'];
                $return = PageLayoutListener::checkModules($pageModel, [], []);
                $moduleIds = $return['moduleIds'];

                if (empty($moduleIds)) {
                    $layout = LayoutModel::findById($pageModel->layout);
                    $moduleIds = StringUtil::deserialize($layout->modules);

                    $sql = "SELECT id,pid,privacyPolicy FROM tl_ncoi_cookie ";
                    /** @var Statement $stmt */
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $modules = $stmt->fetchAll();

                    foreach ($modules as $module) {
                        foreach ($moduleIds as $moduleId) {
                            if ($module['pid'] == $moduleId['mod']) {
                                foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                                    if ($module['pid'] == $externalMediaCookieInDB['pid']) {
                                        $blockedIFrames[] = $externalMediaCookieInDB['cookieToolsSelect'];
                                        $cookieIds[] = $externalMediaCookieInDB['id'];
                                        $modId = $module['pid'];

                                        if (!empty(PageModel::findById($module['privacyPolicy']))) {
                                            $privacyPolicyLink = PageModel::findById($module['privacyPolicy']);
                                            $privacyPolicyLink = $privacyPolicyLink->getFrontendUrl();
                                        }
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
        $id = uniqid();
        switch($iframeTypInHtml) {
            case 'youtube':
                $htmlDisclaimer .=  $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['video'].' <a href="https://policies.google.com/privacy" target="_blank">YouTube</a>.';
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="' . $iconPath . 'youtube-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" type="checkbox" name="'.$blockClass.'" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Youtube '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'</span></label>';
                break;
            case 'googleMaps':
                $htmlDisclaimer .= $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['map'].' <a href="https://policies.google.com/privacy" target="_blank">Google LLC</a>.';
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'map-marker-alt-solid.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Google Maps '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'</span></label>';
                break;
            case 'vimeo':
                $htmlDisclaimer .= $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['video'].' <a href="https://vimeo.com/privacy" target="_blank">Vimeo</a>.';
                $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'vimeo-v-brands.svg"></div>';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked--vimeo" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>Vimeo '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'</span></label>';
                break;
            case 'iframe':
            default:
                global $objPage;
                $htmlDisclaimer .= $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['iframe'].' <a href="/'.$privacyPolicyLink.'" target="_blank">'.$objPage->rootTitle.'</a>.';
                $htmlReleaseAll = '<input id="'.$id.'" name="'.$blockClass.'" type="checkbox" class="ncoi---sliding ncoi---blocked" data-block-class="'.$blockClass.'"><label for="'.$id.'" class="ncoi--release-all ncoi---sliding ncoi---hidden"><i></i><span>iFrames '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'</span></label>';
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
        $htmlConsentButtonEnd = '<span>' . $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['load'].'</span></button></div>';
        $htmlInputCurrentPage = '<input class="ncoi---no-script--hidden" type="text" name="currentPage" value="'.$_SERVER['REDIRECT_URL'].'">';
        $htmlInputModID = '<input class="ncoi---no-script--hidden" type="text" name="data[modId]" value="'.$modId.'">';

        //Damit JS das iFrame wieder von base64 in ein HTML iFrame umwandel kann.
        $iframe = '<script type="text/template">' . base64_encode($iframeHTML) . '</script>';

        $newBuffer = $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlForm . $htmlConsentButton . $htmlIcon . $htmlConsentButtonEnd . $htmlInputCurrentPage .$htmlInputModID .$htmlFormEnd  .$htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;

        $isUserCookieDontAllowMedia = false;
        if (isset($_SESSION) && isset($_SESSION['_sf2_attributes']) && isset($_SESSION['_sf2_attributes']['ncoi']) && isset($_SESSION['_sf2_attributes']['ncoi']['cookieIds'])) {
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
        //Datenbank und User Cookie
        $container = System::getContainer();
        $requestStack = $container->get('request_stack');

        $analyticsType = '';

        if (!empty($requestStack)) {

            // cookie tool select finden
            if (strpos($buffer, 'googletagmanager.com') !== false) {
                $analyticsType = 'googleAnalytics';
            }else {
                $analyticsType = 'matomo';
            }
        }
        //class hinzufügen damit die in JS genutzt werden kann
        $buffer = str_replace('<script','<script class="analytics-decoded-'.$analyticsType.'"',$buffer);
        return '<script id="analytics-encoded-'.$analyticsType.'"><!-- '.base64_encode($buffer).' --></script>';
    }
}
