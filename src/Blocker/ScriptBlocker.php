<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Database;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolRepository;
use Netzhirsch\CookieOptInBundle\Resources\contao\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Logger\Logger;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use DOMDocument;
use DOMElement;
use Contao\System;

class ScriptBlocker
{
    /**
     * @param              $buffer
     * @param Database     $conn
     * @param RequestStack $requestStack
     *
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception*@throws NonUniqueResultException
     */
    public function script(
        $buffer,
        Database $database,
        RequestStack $requestStack,
        ParameterBag $parameterBag,
        CookieToolRepository $cookieToolRepository,
        InsertTagParser $insertTagParser
    ): string {
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
                $newBuffer .= $this->getScriptHTML(
                    $DOMElement,
                    $requestStack,
                    $database,
                    $buffer,
                    $cookieToolRepository,
                    $parameterBag,
                    $insertTagParser
                );
        }
        return $newBuffer;
    }

    /**
     * @param DOMElement $DOMElement
     * @param RequestStack $requestStack
     * @param Database $database
     * @param                      $buffer
     * @param CookieToolRepository $cookieToolRepository
     * @param ParameterBag $parameterBag
     * @param InsertTagParser $insertTagParser
     *
     * @return null|string
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    private function getScriptHTML(
        DOMElement $DOMElement,
        RequestStack $requestStack,
        Database $database,
        $buffer,
        CookieToolRepository $cookieToolRepository,
        ParameterBag $parameterBag,
        InsertTagParser $insertTagParser
    ): ?string {

        $moduleData = Blocker::getModulData($requestStack,$database,$parameterBag);
        $container = System::getContainer();
        if (empty($moduleData)) {
            if ($container->getParameter('kernel.debug'))
                Logger::logExceptionInContaoSystemLog('Request empty for'.$buffer);
            return $buffer;
        }

        $src = self::getSrc($DOMElement)??'';
        $url = Blocker::getLevelUrl($src);
        $modIds = [];
        foreach ($moduleData as $moduleDatum) {
            $modIds[] = $moduleDatum['mod'];
        }
        $sourceIds = array_merge(Blocker::getModIdByInsertTagInModule($database,$modIds,$insertTagParser),$modIds);
        if (!empty($url)) {
            /** @var CookieTool $cookieTool */
            $cookieTool = $cookieToolRepository->findOneBySourceIdAndUrl($sourceIds, $url);
        }
        if (empty($cookieTool)) {
            $cookieTool = $cookieToolRepository->findOneBySourceIdAndType($sourceIds, 'script');
        }
        if (empty($cookieTool)) {
            return $buffer;
        }
        $dataFromExternalMediaAndBar = new DataFromExternalMediaAndBar();
        $dataFromExternalMediaAndBar = Blocker::getDataFromExternalMediaAndBar(
            $DOMElement->textContent,
            $dataFromExternalMediaAndBar,
            $cookieTool
        );
        $dataFromExternalMediaAndBar->setDisclaimer($cookieTool->getIFrameBlockedText());
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
            $buffer,
            $insertTagParser
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