<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class VideoPreviewBlocker
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

        return $this->getIframeHTML($buffer,$requestStack,$conn);
    }

    /**
     * @param $html
     * @param $requestStack
     * @param $conn
     * @param string $html
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getIframeHTML($html,$requestStack,$conn)
    {
        //Frontendvariablen diese werden an das Template übergeben
        $iframeTypInHtml = Blocker::getIFrameType($html);

        $moduleData = Blocker::getModulData($requestStack);
        if (empty($moduleData))
            return $html;

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $url = $this->getUrl($html);
        if (!empty($url))
            $externalMediaCookiesInDB = Blocker::getExternalMediaByUrl($conn, $url);
        if (empty($externalMediaCookiesInDB)) {
            $externalMediaCookiesInDB = Blocker::getExternalMediaByType($html,$conn);
            $dataFromExternalMediaAndBar->setIFrameType(Blocker::getIFrameType($html));
        }
        if (empty($externalMediaCookiesInDB))
            return $html;

        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $dataFromExternalMediaAndBar,
            $conn,
            $externalMediaCookiesInDB,
            $moduleData
        );

        $isIFrameTypInDB = false;
        $blockedIFrames = $dataFromExternalMediaAndBar->getBlockedIFrames();
        if (in_array($iframeTypInHtml,$blockedIFrames) || empty($dataFromExternalMediaAndBar->getDisclaimer()))
            $isIFrameTypInDB = true;

        if (!$isIFrameTypInDB)
            return $html;

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

        $imageSrc = $this->getImageSrc($html);

        $innerFigure = substr($html,strpos($html,'<figure'));
        $innerFigure = substr($innerFigure,0,strpos($innerFigure,'</figure>'));
        $sizeIframe = $this->getIframeSize($innerFigure);

        $html = $this->replacePreviewImageWithIframe($html,$innerFigure,$sizeIframe);

        $newBuffer = Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $sizeIframe,
            $html,
            $iconPath
        );

        $sizeBackground = [
            'height' => self::getHeight($html),
            'width' => self::getWidth($html)
        ];

        if (!empty($sizeBackground['height']) && !Blocker::hasUnit($sizeBackground['height']))
            $sizeBackground['height'] .= 'px';
        if (!empty($sizeBackground['width']) &&  !Blocker::hasUnit($sizeBackground['width']))
            $sizeBackground['width'] .= 'px';

        $search = 'style="';
        $replace = $search.' background-image:url('.$imageSrc.');
                background-repeat: no-repeat;
                background-position: center;';
        if (!empty($sizeBackground['width']) || !empty($sizeBackground['height']))
            $replace .=  'background-size: '.$sizeBackground['width'].' '.$sizeBackground['height'].';';

        $newBuffer = str_replace($search, $replace, $newBuffer);

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
            return $html;
        } else {
            return $newBuffer;
        }
    }

    private function replacePreviewImageWithIframe(string $html,string $imageSrc, array $size){

        $src = self::getFullURL($html);

        $iframe = '<iframe src="'.$src.'" allowfullscreen="" width="'.$size['width'].'" height="'.$size['height'].'"></iframe>';
        $imageSrc = str_replace($imageSrc,$iframe,$html);
        return $imageSrc;
    }

    private function getImageSrc(string $html){
        $imageSrc = substr($html,strpos($html,'src="'));
        $imageSrc = str_replace('src="','',$imageSrc);
        $imageSrc = substr($imageSrc,0,strpos($imageSrc,'"'));
        return $imageSrc;
    }

    private static function getUrl(string $html){

        $htmlUrlPart = self::getFullURL($html);

        $urlArray = explode('/',$htmlUrlPart);
        foreach ($urlArray as $url) {
            if (strpos($url,'.'))
                return $url;
        }

        return '';
    }

    private static function getFullURL(string $html){

        $htmlUrlPart = substr($html,strpos($html,'href="'));
        $htmlUrlPart = str_replace('href="','',$htmlUrlPart);
        $htmlUrlPart = str_replace('www.','',$htmlUrlPart);
        return substr($htmlUrlPart,0,strpos($htmlUrlPart,'"'));

    }

    private function getIframeSize($html): array
    {
        $position = self::getPosition($html,'iframe.width = ');
        $width = substr($html,$position);
        $width = substr($width,0,strpos($width,';'));
        $width = str_replace('\'','',$width);
        $size['width'] = $width;

        $position = self::getPosition($html,'iframe.height = ');
        $height = substr($html,$position);
        $height = substr($height,0,strpos($height,';'));
        $height = str_replace('\'','',$height);
        $size['height'] = $height;

        return $size;
    }

    private static function getHeight($iframeHTML): string
    {
        $position = self::getPosition($iframeHTML,'max-height:');
        if (!empty($position))
            return '';

        $position = self::getPosition($iframeHTML,'height="');
        if (!empty($position))
            return self::getSizeFromAttribute($iframeHTML,$position);

        $position = self::getPosition($iframeHTML,'height:');
        if (!empty($position))
            return self::getSizeFromStyle($iframeHTML,$position);

        return '';
    }

    private static function getWidth($iframeHTML) :string{
        $position = self::getPosition($iframeHTML,'max-width:') ;
        if (!empty($position))
            return '';

        $position = self::getPosition($iframeHTML,'width="');
        if (!empty($position))
            return self::getSizeFromAttribute($iframeHTML,$position);

        $position = self::getPosition($iframeHTML,'width:');
        if (!empty($position))
            return self::getSizeFromStyle($iframeHTML,$position);

        return '';
    }

    private static function getSizeFromAttribute(string $iframeHTML,int $position) {
        return self::getSizeFrom($iframeHTML,$position,'"');
    }

    private static function getSizeFromStyle(string $iframeHTML,int $position) {
        return self::getSizeFrom($iframeHTML,$position,';');
    }

    private static function getSizeFrom(string $iframeHTML,int $position,string $needle) {
        if (empty($position))
            return '';
        $size = substr($iframeHTML, $position);
        $position = strpos($size, $needle);
        $size = substr($size, 0,$position);
        if (strpos($size, 'figure') !== false)
            return '';
        return $size;
    }

    private static function getPosition($iframeHTML,$needle): int
    {
        $heightPosition = strpos($iframeHTML, $needle);
        if ($heightPosition === false)
            return 0;

        return $heightPosition + strlen($needle);
    }
}