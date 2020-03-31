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
        //IFrame als HTML Element eingebunden
        if ($template == 'ce_html' && strpos($buffer, '<iframe') !== false) {

            // Block Entscheidungsvariablen
            $isUserCookieDontAllowMedia = false;
            $blockedIFrames = [];

            //Datenbank und User Cookie
            $container = System::getContainer();
            $requestStack = $container->get('request_stack');

            //Frontendvariablen
            $iframeTypInHtml = 'iframe';
            $privacyPolicyLink = '';

            //Wenn null werden alle IFrames angezeigt.
            if (!empty($requestStack)) {

                //Type des IFrames suchen damit danach in der Datenbank gesucht werden kann
                if (strpos($buffer, 'www.youtube') !== false) {
                    $iframeTypInHtml = 'youtube';
                }elseif (strpos($buffer, 'player.vimeo') !== false) {
                    $iframeTypInHtml = 'vimeo';
                }elseif (strpos($buffer, 'www.google') !== false) {
                    $iframeTypInHtml = 'googleMaps';
                }

                //Suche nach dem IFrame.
                /** @noinspection MissingService */
                $conn = $container->get('database_connection');
                $sql = "SELECT id,pid,cookieToolsSelect FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";
                /** @var Statement $stmt */
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(1, 'cookieTools');
                $stmt->bindValue(2, $iframeTypInHtml);
                $stmt->execute();
                $externalMediaCookiesInDB = $stmt->fetchAll();

                //Im Cookie gesetzten IFrame finden, damit dieses nicht blocked werden kann.
                $cookieData = CookieController::getUserCookie($requestStack);
                foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                    if (!empty($cookieData->getOtherCookieIds()) && in_array($externalMediaCookieInDB['id'], $cookieData->getOtherCookieIds())) {
                        $isUserCookieDontAllowMedia = true;
                        break;
                    }
                }

                //Feststellen ob IFrame laut Backend geblocked werden soll
                // und Datenschutz url finden
                $attributes = $requestStack->getCurrentRequest()->attributes;
                if (!empty($attributes)) {
                    /** @var PageModel $pageModel */
                    $pageModel = $attributes->get('pageModel');
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
             * IFrame spezifisches HTML
             */
            $blockClass = 'ncoi---'.$iframeTypInHtml;
            switch($iframeTypInHtml) {
                case 'youtube':
                    $htmlDisclaimer .=  $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['video'].' <a href="https://policies.google.com/privacy" target="_blank">YouTube</a>.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="' . $iconPath . 'youtube-brands.svg"></div>';
                    $htmlReleaseAll = '<label class="ncoi--release-all">Youtube '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'<input type="checkbox" name="'.$blockClass.'" class="ncoi---blocked" data-block-class="'.$blockClass.'"></label>';
                    break;
                case 'googleMaps':
                    $htmlDisclaimer .= $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['map'].' <a href="https://policies.google.com/privacy" target="_blank">Google LLC</a>.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'map-marker-alt-solid.svg"></div>';
                    $htmlReleaseAll = '<label class="ncoi--release-all">Google Maps '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'<input name="'.$blockClass.'" type="checkbox" class="ncoi---blocked" data-block-class="'.$blockClass.'"></label>';
                    break;
                case 'vimeo':
                    $htmlDisclaimer .= $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['video'].' <a href="https://vimeo.com/privacy" target="_blank">Vimeo</a>.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'vimeo-v-brands.svg"></div>';
                    $htmlReleaseAll = '<label class="ncoi--release-all">Vimeo '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'<input name="'.$blockClass.'" type="checkbox" class="ncoi---blocked--vimeo" data-block-class="'.$blockClass.'"></label>';
                    break;
                case 'iframe':
                default:
                    global $objPage;
                    $htmlDisclaimer .= $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['iframe'].' <a href="/'.$privacyPolicyLink.'" target="_blank">'.$objPage->rootTitle.'</a>.';
                    $htmlReleaseAll = '<label class="ncoi--release-all">IFrames '.$GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['alwaysLoad'].'<input name="'.$blockClass.'" type="checkbox" class="ncoi---blocked" data-block-class="'.$blockClass.'"></label>';
                    break;
            }
            $htmlDisclaimer .= '</div>';

            // Wenn IFrame nicht im Backend, kann nur das IFrame zurückgegeben werden.
            $isIFrameTypInDB = false;
            if (in_array($iframeTypInHtml,$blockedIFrames))
                $isIFrameTypInDB = true;

            if (!$isIFrameTypInDB)
                return $buffer;

            //$blockclass im JS um blocked Container ein. und auszublenden
            $class = 'ncoi---blocked ncoi---iframes '.$blockClass;

            //User möchte das IFrame sehen
            if ($isUserCookieDontAllowMedia)
                $class .= ' ncoi---hidden';

            // Abmessungen des Block Container, damit es die gleiche Göße wie das IFrame hat.
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

            //Damit JS das IFrame wieder laden kann
            $htmlConsentLink = '<div class="ncoi---blocked-link"><a href="#" class="ncoi---release" title="erlauben">';
            $htmlConsentLinkEnd = $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['iframes']['load'].'</a></div>';

            //Damit JS das IFrame wieder von base64 in ein HTML IFrame umwandel kann.
            $iframe = '<script type="text/template">' . base64_encode($buffer) . '</script>';

            $newBuffer = $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlConsentLink . $htmlIcon . $htmlConsentLinkEnd . $htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;

            //User möchte das IFrame sehen, aber vielleicht auch über JS wieder blocken
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