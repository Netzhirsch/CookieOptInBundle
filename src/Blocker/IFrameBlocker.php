<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class IFrameBlocker
{
    /**
     * @param $buffer
     * @param Connection $conn
     * @param RequestStack $requestStack
     * @return string
     * @throws DBALException
     * @throws Exception
     */
    public function iframe($buffer,Connection $conn, RequestStack$requestStack){

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
     * @param $conn
     * @param string $html
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getIframeHTML($iframeHTML,$requestStack,$conn)
    {
        // Speicher blockierte IFrame Typen
        $blockedIFrames = [];

        //Frontendvariablen diese werden an das Template übergeben
        $iframeTypInHtml = Blocker::getIFrameType($iframeHTML);

        $moduleData = Blocker::getModulData($requestStack);
        if (empty($moduleData))
            return $iframeHTML;

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $url = $this->getUrl($iframeHTML);
        if (!empty($url))
            $externalMediaCookiesInDB = Blocker::getExternalMediaByUrl($conn, $url);
        if (empty($externalMediaCookiesInDB)) {
            $externalMediaCookiesInDB = Blocker::getExternalMediaByType($iframeHTML,$conn);
            $dataFromExternalMediaAndBar->setIFrameType(Blocker::getIFrameType($iframeHTML));
        }
        if (empty($externalMediaCookiesInDB))
            return $iframeHTML;

        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $dataFromExternalMediaAndBar,
            $conn,
            $externalMediaCookiesInDB,
            $moduleData
        );
        // Wenn iFrame nicht im Backend, kann nur das iFrame zurückgegeben werden.
        $isIFrameTypInDB = false;
        if (in_array($iframeTypInHtml,$blockedIFrames) || empty($dataFromExternalMediaAndBar->getDisclaimer()))
            $isIFrameTypInDB = true;

        if (!$isIFrameTypInDB)
            return $iframeHTML;

        // alle icons liegen im gleich Ordner
        // root der bundle assets
        $iconPath = 'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR;
        $barRepo = new BarRepository($conn);
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
        $heightPosition = strpos($iframeHTML, 'height="')+strlen('height="');
        $height = substr($iframeHTML, $heightPosition);
        $heightPosition = strpos($height, '"');
        $height = substr($height, 0,$heightPosition);

        $widthPosition = strpos($iframeHTML, 'width="')+strlen('width="');
        $width = substr($iframeHTML, $widthPosition);
        $widthPosition = strpos($width, '"');
        $width = substr($width, 0,$widthPosition);
        $size = [
            'height' => $height,
            'width' => $width
        ];
        $newBuffer = Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $size,
            $iframeHTML,
            $iconPath,
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
}