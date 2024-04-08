<?php

namespace Netzhirsch\CookieOptInBundle\Blocker;

use Contao\Database;
use DOMDocument;
use DOMElement;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Logger\Logger;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\ToolRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;

class C4gmapBlocker
{
    /**
     * @param $buffer
     * @param Database $database
     * @param RequestStack $requestStack
     * @return mixed
     * @throws DBALException
     * @throws DriverException
     */
    public function block($buffer,Database $database,RequestStack $requestStack) {

        if (empty($requestStack))
            return $buffer;

        $newBuffer = $this->getHtml($buffer,$database,$requestStack);
        if (!empty($newBuffer))
            return $newBuffer;

        return $buffer;
    }

    /**
     * @param $buffer
     * @param Database $database
     * @param $requestStack
     * @return null
     * @throws DriverException
     * @throws DBALException
     */
    private function getHtml($buffer,Database $database,$requestStack) {

        $moduleData = Blocker::getModulData($requestStack,$database);
        if (empty($moduleData))
            return $buffer;

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $toolRepo = new ToolRepository($database);
        $externalMediaCookiesInDB = $toolRepo->findByType('c4g_map');
        if (empty($externalMediaCookiesInDB))
            return $buffer;

        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $dataFromExternalMediaAndBar,$database,$externalMediaCookiesInDB,$moduleData
        );

        $barRepo = new BarRepository($database);
        $blockText = $barRepo->loadBlockContainerTexts($dataFromExternalMediaAndBar->getModId());

        if (Blocker::noScriptFallbackRenderScript($dataFromExternalMediaAndBar))
            return $buffer;

        $doc = new DOMDocument();
        if (self::isDebugModus($buffer)) {
            Logger::logExceptionInContaoSystemLog('HTML is no valid'.$buffer);
            return $buffer;
        }
        @$doc->loadHTML($buffer);
        $divs = @$doc->getElementsByTagName('div');

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
            '',
            'bundles' . DIRECTORY_SEPARATOR . 'netzhirschcookieoptin' . DIRECTORY_SEPARATOR
        );
        $html .='</div>';
        $buffer = str_replace('mod_c4g_maps','mod_c4g_maps ncoi---custom_gmap ncoi---hidden',$buffer);
        return $html.$buffer;
    }

    private static function isDebugModus($buffer)
    {
        $debugCommentPosition = strpos($buffer,'<!-- TEMPLATE START: templates/layout/customelement_gmap.html5 -->');
        return ($debugCommentPosition !== false);
    }
}

