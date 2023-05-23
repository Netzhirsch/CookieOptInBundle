<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\Database;
use Contao\LayoutModel;
use Doctrine\DBAL\Driver\Exception;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class IFrameBlocker
{
    /**
     * @param $buffer
     * @param Database $database
     * @param RequestStack $requestStack
     * @return string
     */
    public function iframe($buffer,Database $database, RequestStack $requestStack,ParameterBag $parameterBag) {

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
                    try {
                        $return .= $this->getIframeHTML($iframeHTML, $requestStack, $database,$parameterBag);
                    } catch (Exception $e) {
                        return $buffer;
                    }
                }
                $return .= substr($html,$iframeArray,strlen($html));
            } else {
                $return .= $html;
            }
        }
        return $return;
    }

    /**
     * @param $iframeHTML
     * @param $requestStack
     * @param Database $database
     * @return string
     */
    private function getIframeHTML($iframeHTML,$requestStack, Database $database,ParameterBag $parameterBag)
    {
        //Frontendvariablen diese werden an das Template übergeben
        $iframeTypInHtml = Blocker::getIFrameType($iframeHTML);

        $moduleData = Blocker::getModulData($requestStack,$database,$parameterBag);
        if (empty($moduleData))
            return $iframeHTML;

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $url = $this->getUrl($iframeHTML);
        if (!empty($url))
            $externalMediaCookiesInDB = Blocker::getExternalMediaByUrl($database, $url);
        if (empty($externalMediaCookiesInDB)) {
            $externalMediaCookiesInDB = Blocker::getExternalMediaByType($iframeHTML,$database);
            $dataFromExternalMediaAndBar->setIFrameType(Blocker::getIFrameType($iframeHTML));
        }
        if (empty($externalMediaCookiesInDB))
            return $iframeHTML;

        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $dataFromExternalMediaAndBar,
            $database,
            $externalMediaCookiesInDB,
            $moduleData
        );
        if (empty($dataFromExternalMediaAndBar->getModId())) {
            global $objPage;
            $return = PageLayoutListener::checkModules(LayoutModel::findById($objPage->layout),$database, [], [],$parameterBag);
            $moduleData[] =['mod' => $return['moduleIds'][0]];
            $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
                $dataFromExternalMediaAndBar,
                $database,
                $externalMediaCookiesInDB,
                $moduleData
            );
        }
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
        // Abmessungen des Block Container, damit es die gleiche Göße wie das iFrame hat.
        $size = [
            'height' => self::getHeight($iframeHTML),
            'width' => self::getWidth($iframeHTML)
        ];

        $newBuffer = Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $size,
            $iframeHTML,
            $iconPath
        );
        $isUserCookieDontAllowMedia = false;
        if (
            isset($_SESSION)
            && isset($_SESSION['_sf2_attributes'])
            && isset($_SESSION['_sf2_attributes']['ncoi'])
            && isset($_SESSION['_sf2_attributes']['ncoi']['cookieIds'])
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

    private static function getHeight($iframeHTML): string
    {
        $position = self::getPosition($iframeHTML,'height="') ;
        if ($byStyle = empty($position))
            $position = self::getPosition($iframeHTML,'height:');

        return self::getSize($iframeHTML,$position,$byStyle);
    }

    private static function getWidth($iframeHTML) :string{
        $position = self::getPosition($iframeHTML,'width="') ;
        if ($byStyle = empty($position))
            $position = self::getPosition($iframeHTML,'width:');

        return self::getSize($iframeHTML,$position,$byStyle);
    }

    private static function getSize($iframeHTML,$position,$byStyle = false): string{

        $size = substr($iframeHTML, $position);
        if ($byStyle)
            $position = strpos($size, ';');
        else
            $position = strpos($size, '"');
        return substr($size, 0,$position);

    }
    private static function getPosition($iframeHTML,$needle): int
    {
        $heightPosition = strpos($iframeHTML, $needle);
        if ($heightPosition === false)
            return 0;

        return $heightPosition + strlen($needle);
    }
}