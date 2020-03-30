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
            $isConsentBackend = [];
            $buffer = str_replace('" frameborder="0"','?'.time().'" frameborder="0"',$buffer);
            $buffer = str_replace('" width="600"','?'.time().'" width="600"',$buffer);
            $isUserCookieDontAllowMedia = false;
            //Datenbank und User Cookie
            $container = System::getContainer();
            $requestStack = $container->get('request_stack');

            $iframeTypInHtml = 'iframe';
            if (!empty($requestStack)) {

                if (strpos($buffer, 'www.youtube') !== false) {
                    $iframeTypInHtml = 'youtube';
                }elseif (strpos($buffer, 'player.vimeo') !== false) {
                    $iframeTypInHtml = 'vimeo';
                }elseif (strpos($buffer, 'www.google') !== false) {
                    $iframeTypInHtml = 'googleMaps';
                }

                //Suche nach dem media cookies.
                /** @noinspection MissingService */
                $conn = $container->get('database_connection');
                $sql = "SELECT id,pid,cookieToolsSelect FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";
                /** @var Statement $stmt */
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(1, 'cookieTools');
                $stmt->bindValue(2, $iframeTypInHtml);
                $stmt->execute();
                $externalMediaCookiesInDB = $stmt->fetchAll();

                //Hat der User ein dieses Cookie gesetzt
                $cookieData = CookieController::getUserCookie($requestStack);
                foreach ($externalMediaCookiesInDB as $externalMediaCookieInDB) {
                    if (!empty($cookieData->getOtherCookieIds()) && in_array($externalMediaCookieInDB['id'], $cookieData->getOtherCookieIds())) {
                        $isUserCookieDontAllowMedia = true;
                        break;
                    }
                }

                //Gibt es im Backend dieses Media Cookie
                $attributes = $requestStack->getCurrentRequest()->attributes;
                if (!empty($attributes)) {
                    /** @var PageModel $pageModel */
                    $pageModel = $attributes->get('pageModel');
                    $moduleIds = PageLayoutListener::checkModules($pageModel, [], []);

                    if (empty($moduleIds)) {
                        $layout = LayoutModel::findById($pageModel->layout);
                        $moduleIds = StringUtil::deserialize($layout->modules);

                        $sql = "SELECT id,cookieGroups FROM tl_module WHERE type = ? ";
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
                                            $isConsentBackend[] = $externalMediaCookieInDB['cookieToolsSelect'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //eigene Container immer mit ausgeben, damit über JavaScript wieder angenommen werden kann.
            $htmlDisclaimer = '<div class="ncoi---blocked-disclaimer">';
            $htmlIcon = '';
            $htmlReleaseAll  = '';
            $iconPath = 'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR;

            /**
             * IFrame spezifisches HTML
             * check IFrame in DB
             */
            $blockClass = 'ncoi---'.$iframeTypInHtml;
            switch($iframeTypInHtml) {
                case 'youtube':
                    $htmlDisclaimer .= 'Durch das Laden dieses Video stimmen Sie den Datenschutzbedingungen von <a href="https://policies.google.com/privacy" target="_blank">YouTube</a> zu.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="' . $iconPath . 'youtube-brands.svg"></div>';
                    $htmlReleaseAll = '<label class="ncoi--release-all">Youtube immer laden<input type="checkbox" name="'.$blockClass.'" class="ncoi---blocked" data-block-class="'.$blockClass.'"></label>';
                    break;
                case 'googleMaps':
                    $htmlDisclaimer .= 'Durch das Laden dieser Karte stimmen Sie den Datenschutzbedingungen von <a href="https://policies.google.com/privacy" target="_blank">Google LLC</a> zu.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'map-marker-alt-solid.svg"></div>';
                    $htmlReleaseAll = '<label class="ncoi--release-all">Google Maps immer laden<input name="'.$blockClass.'" type="checkbox" class="ncoi---blocked" data-block-class="'.$blockClass.'"></label>';
                    break;
                case 'vimeo':
                    $htmlDisclaimer .= 'Durch das Laden dieser Karte stimmen Sie den Datenschutzbedingungen von <a href="https://vimeo.com/privacy" target="_blank">Vimeo</a> LLC zu.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="map-marker" src="' . $iconPath . 'vimeo-v-brands.svg"></div>';
                    $htmlReleaseAll = '<label class="ncoi--release-all">Vimeo immer laden<input name="'.$blockClass.'" type="checkbox" class="ncoi---blocked--vimeo" data-block-class="'.$blockClass.'"></label>';
                    break;
                case 'iframe':
                    $htmlDisclaimer .= 'Durch das Laden dieses IFrames stimmen Sie den Datenschutzbedingungen von '.$_SERVER['HTTP_HOST'].' zu.';
                    $htmlReleaseAll = '<label class="ncoi--release-all">IFrames immer laden<input name="'.$blockClass.'" type="checkbox" class="ncoi---blocked" data-block-class="'.$blockClass.'"></label>';
            }

            $isIFrameTypInDB = false;
            if (in_array($iframeTypInHtml,$isConsentBackend))
                $isIFrameTypInDB = true;

            if (!$isIFrameTypInDB) {
                return $buffer;
            }
            $class = 'ncoi---blocked ncoi---iframes '.$blockClass;

            if ($isUserCookieDontAllowMedia) {
                $class .= ' ncoi---hidden';
            }

            // Abmessungen des Block Container
            $height = substr($buffer, strpos($buffer, 'height'), 11);
            $height = substr($height, 8, 3);

            $width = substr($buffer, strpos($buffer, 'width'), 11);
            $width = substr($width, 7, 3);

            $htmlContainer = '<div class="'.$class.'" style="height:' . $height . 'px; width:' . $width . 'px" >';
            $htmlContainerEnd = '</div>';

            $htmlConsentBox = '<div class="ncoi---consent-box">';
            $htmlConsentBoxEnd = '</div>';

            $htmlConsentLink = '<div class="ncoi---blocked-link"><a href="#" class="ncoi---release" title="erlauben">';
            $htmlConsentLinkEnd = 'laden</a></div>';

            $htmlDisclaimer .= '</div>';

            $iframe = '<script type="text/template">' . base64_encode($buffer) . '</script>';

            $newBuffer = $htmlContainer  .$htmlConsentBox . $htmlDisclaimer . $htmlConsentLink . $htmlIcon . $htmlConsentLinkEnd . $htmlReleaseAll . $htmlConsentBoxEnd . $iframe .$htmlContainerEnd;

            if ($isUserCookieDontAllowMedia) {
                return $buffer.$newBuffer;
            } else {
                return $newBuffer;
            }
        }
        return $buffer;
    }
}