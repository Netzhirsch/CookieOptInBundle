<?php
namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Netzhirsch\CookieOptInBundle\Controller\CookieController;

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

            // Block Entscheidungsvariablen
            $isUserCookieDontAllowMedia = false;
            $blockedIFrames = [];

            //Datenbank und User Cookie
            $container = System::getContainer();
            $requestStack = $container->get('request_stack');

            //Frontendvariablen
            $iframeTypInHtml = 'iframe';
            $privacyPolicyLink = '';
            $modID = null;

            //Wenn null werden alle iFrames angezeigt.
            if (!empty($requestStack)) {

                //Type des iFrames suchen damit danach in der Datenbank gesucht werden kann
                if (strpos($buffer, 'youtube') !== false || strpos($buffer, 'youtu.be') !== false) {
                    $iframeTypInHtml = 'youtube';
                }elseif (strpos($buffer, 'player.vimeo') !== false) {
                    $iframeTypInHtml = 'vimeo';
                }elseif (strpos($buffer, 'google.com/maps') || strpos($buffer, 'maps.google') !== false) {
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

                //Im Cookie gesetzten iFrame finden, damit dieses nicht blocked werden kann.
                $cookieData = CookieController::getUserCookie($requestStack);
                foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                    if (!empty($cookieData->getOtherCookieIds()) && in_array($externalMediaCookieInDB['id'], $cookieData->getOtherCookieIds())) {
                        $isUserCookieDontAllowMedia = true;
                        break;
                    }
                }

                //Feststellen ob iFrame laut Backend geblocked werden sollen
                // und Datenschutz url finden
                $attributes = $requestStack->getCurrentRequest()->attributes;
                if (!empty($attributes)) {
                    /** @var PageModel $pageModel */
                    $pageModel = $attributes->get('pageModel');
                    // Contao 4.4
                    if (empty($pageModel))
                        $pageModel = $GLOBALS['objPage'];
                    $moduleIds = PageLayoutListener::checkModules($pageModel, [], []);

                    if (empty($moduleIds)) {
                        $layout = LayoutModel::findById($pageModel->layout);
                        $moduleIds = StringUtil::deserialize($layout->modules);

                        $sql = "SELECT id,cookieGroups,privacyPolicy FROM tl_module WHERE type = ? ";
                        /** @var Statement $stmt */
                        $stmt = $conn->prepare($sql);
                        $stmt->bindValue(1, 'cookieOptInBar');
                        $stmt->execute();
                        $modules = $stmt->fetchAll();

                        foreach ($modules as $module) {
                            foreach ($moduleIds as $moduleId) {
                                if ($module['id'] == $moduleId['mod']) {
                                    foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                                        if ($module['id'] == $externalMediaCookieInDB['pid']) {
                                            $blockedIFrames[] = $externalMediaCookieInDB['cookieToolsSelect'];
                                            $modID = $module['id'];

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
                return $buffer;

            //$blockclass im JS um blocked Container ein. und auszublenden
            $class = 'ncoi---blocked ncoi---iframes '.$blockClass;

            //User möchte das iFrame sehen
            if ($isUserCookieDontAllowMedia)
                $class .= ' ncoi---hidden';

            // Abmessungen des Block Container, damit es die gleiche Göße wie das iFrame hat.
            $height = substr($buffer, strpos($buffer, 'height'), 11);
            $height = substr($height, 8, 3);

            $width = substr($buffer, strpos($buffer, 'width'), 11);
            $width = substr($width, 7, 3);

            //Umschliedender Container damit Kinder zentiert werden könne
            $htmlContainer = '<div class="'.$class.'" style="height:' . $height . 'px; width:' . $width . 'px" >';
            $htmlContainerEnd = '</div>';

            //Container für alle Inhalte
            $htmlConsentBox = '<div class="ncoi---consent-box">';
            $htmlConsentBoxEnd = '</div>';

            $htmlForm = '<!--suppress HtmlUnknownTarget --><form action="/cookie/allowed/iframe" method="post">';
            $htmlFormEnd = '</form>';
            //Damit JS das iFrame wieder laden kann
            $htmlConsentButton = '<div class="ncoi---blocked-link">
<button type="submit" name="iframe" value="'.$iframeTypInHtml.'" class="ncoi---release">';
            $htmlConsentButtonEnd = '<span>' . $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['load'].'</span></button></div>';
            $htmlInputCurrentPage = '<input class="ncoi---no-script--hidden" type="text" name="currentPage" value="'.$_SERVER['REDIRECT_URL'].'">';
            $htmlInputModID = '<input class="ncoi---no-script--hidden" type="text" name="modID" value="'.$modID.'">';

            //Damit JS das iFrame wieder von base64 in ein HTML iFrame umwandel kann.
            $iframe = '<script type="text/template">' . base64_encode($buffer) . '</script>';

            $newBuffer = $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlForm . $htmlConsentButton . $htmlIcon . $htmlConsentButtonEnd . $htmlInputCurrentPage .$htmlInputModID .$htmlFormEnd  .$htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;

            //User möchte das iFrame sehen, aber vielleicht auch über JS wieder blocken
            if ($isUserCookieDontAllowMedia) {
                return $buffer.$newBuffer;
            } else {
                return $newBuffer;
            }
        }
        // nichts ändern
        return $buffer;
    }
}
