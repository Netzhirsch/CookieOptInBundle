<?php

namespace Netzhirsch\CookieOptInBundle\Blocker;

use Doctrine\DBAL\Connection;
use DOMDocument;
use DOMElement;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;

class CustomGmapBlocker
{
    /**
     * @param $buffer
     * @param Connection $conn
     * @param RequestStack $requestStack
     * @return mixed
     * @throws DBALException
     * @throws DriverException
     */
    public function block($buffer,Connection $conn,RequestStack $requestStack) {

        if (empty($requestStack))
            return $buffer;

        $newBuffer = $this->getCustomGmapHtml($buffer,$conn,$requestStack);
        if (!empty($newBuffer))
            return $newBuffer;

        return $buffer;
    }

    /**
     * @param $buffer
     * @param $conn
     * @param $requestStack
     * @return null
     * @throws DriverException
     * @throws DBALException
     */
    private function getCustomGmapHtml($buffer,$conn,$requestStack) {

        $moduleData = Blocker::getModulData($requestStack);
        if (empty($moduleData))
            return $buffer;

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $externalMediaCookiesInDB = Blocker::getExternalMediaByType('maps.google',$conn,'googleMaps');
        if (empty($externalMediaCookiesInDB))
            return $buffer;

        $dataFromExternalMediaAndBar->setIFrameType('googleMaps');

        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $dataFromExternalMediaAndBar,$conn,$externalMediaCookiesInDB,$moduleData
        );

        $barRepo = new BarRepository($conn);
        $blockText = $barRepo->loadBlockContainerTexts($dataFromExternalMediaAndBar->getModId());

        if (Blocker::isAllowed($dataFromExternalMediaAndBar))
            return $buffer;

        $doc = new DOMDocument();
        if (self::isDebugModus($buffer)) {
            return 'Unfortunately no output in debug mode is possible.';
        }
        $doc->loadHTML($buffer);
        $divs = $doc->getElementsByTagName('div');
        /** @var DOMElement $div */
        $height = '100%';
        foreach ($divs as $div) {
            $style = $div->getAttribute('style');
            if (!empty($style)) {
                $height = str_replace('height:','',$style);
                $height = str_replace(';','',$height);
                if (!Blocker::hasUnit($height))
                    $height .= 'px';
            }
        }
        $size = [
          'height'  => $height,
          'width'  => 'auto',
        ];
        $dataFromExternalMediaAndBar->setDisclaimer($blockText['i_frame_maps']);
        $html = '<div class="ncoi---custom_gmap">';
        $html .= Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockText,
            $size,
            $buffer,
            'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR,
        );
        $html .='</div>';

        $buffer = str_replace('ce_google_map','ce_google_map ncoi---hidden',$buffer);

        return $html.$buffer;
    }

    private static function isDebugModus($buffer)
    {
        $debugCommentPosition = strpos($buffer,'<!-- TEMPLATE START: templates/layout/customelement_gmap.html5 -->');
        return ($debugCommentPosition !== false);
    }
}
