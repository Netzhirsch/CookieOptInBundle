<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\Database;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Logger\Logger;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use DOMDocument;
use DOMElement;
use System;

class ScriptBlocker
{
    /**
     * @param $buffer
     * @param Database $conn
     * @param RequestStack $requestStack
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function script($buffer,Database $database,RequestStack $requestStack) {

        if (empty($requestStack))
            return $buffer;

        /**
         * Scripts von anderen HTML Tags trennen.
         * Scripts encoden und in Container div einbetten.
         * Andere HTML Tags einfach ans Return anhängen.
         */
        $newBuffer = '';
        $doc = new DOMDocument();
//        $clearBuffer = Blocker::clearHtmlComments($buffer);
        $doc->loadHTML($buffer);
        $DOMElements = self::getAllDOMElement($doc);
        foreach ($DOMElements as $DOMElement) {

            $wrapWithBlockContainer = $DOMElement->getAttribute('data-ncoi-no-block-container');
            if (empty($wrapWithBlockContainer))
                $newBuffer .= $this->getScriptHTML($DOMElement,$requestStack,$database,$buffer);
        }
        return $newBuffer;
    }

    /**
     * @param DOMElement $DOMElement
     * @param RequestStack $requestStack
     * @param Connection $conn
     * @param $buffer
     * @return null|string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getScriptHTML(DOMElement $DOMElement, RequestStack $requestStack, Database $database,$buffer){

        $moduleData = Blocker::getModulData($requestStack,$database);
        $container = System::getContainer();
        if (empty($moduleData)) {
            if ($container->getParameter('kernel.debug'))
                Logger::logExceptionInContaoSystemLog('Request empty for'.$buffer);
            return $buffer;
        }

        $src = self::getSrc($DOMElement);
        if (empty($src)) {
            if ($container->getParameter('kernel.debug'))
                Logger::logExceptionInContaoSystemLog('src empty for'.$buffer);
            return $buffer;
        }

        $externalMediaCookiesInDB = Blocker::getExternalMediaByUrl($database, $src);
        if (empty($externalMediaCookiesInDB)) {
            if ($container->getParameter('kernel.debug'))
                Logger::logExceptionInContaoSystemLog('no data found by src:'.$src);
            return $buffer;
        }

        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $dataFromExternalMediaAndBar,$database,$externalMediaCookiesInDB,$moduleData
        );
        $dataFromExternalMediaAndBar->setDisclaimer($externalMediaCookiesInDB[0]['i_frame_blocked_text']);
        $dataFromExternalMediaAndBar->setIFrameType('script');
        $barRepo = new BarRepository($database);
        $blockText = $barRepo->loadBlockContainerTexts($dataFromExternalMediaAndBar->getModId());


        if (Blocker::noScriptFallbackRenderScript($dataFromExternalMediaAndBar)) {
            if ($container->getParameter('kernel.debug'))
                Logger::logExceptionInContaoSystemLog('no script fallback used:');
            return $buffer;
        }

        $size = [
            'height' => $DOMElement->getAttribute('height'),
            'width' => $DOMElement->getAttribute('width'),
        ];

        return Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockText,
            $size,
            $buffer
        );
    }

    private static function getSrc(DOMElement $DOMElement){

        $src = $DOMElement->getAttribute('data-ncoi-src');
        if (!empty($src))
            return $src;

        $src = $DOMElement->getAttribute('src');
        if (!empty($src))
            return $src;


        return null;
    }


    private static function getAllDOMElement(DOMDocument $doc) {
        $domElementArray = [];
        $domElementArray = self::addToDomElementArray($doc,$domElementArray,'script');
        $domElementArray = self::addToDomElementArray($doc,$domElementArray,'link');
        return $domElementArray;
    }

    private static function addToDomElementArray(DOMDocument $doc,$domElementArray,$tag) {
        $scriptElements = $doc->getElementsByTagName($tag);
        foreach ($scriptElements as $scriptElement) {
            $domElementArray[] = $scriptElement;
        }
        return $domElementArray;
    }
}