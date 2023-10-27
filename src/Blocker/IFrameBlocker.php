<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Database;
use Contao\LayoutModel;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Netzhirsch\CookieOptInBundle\Resources\contao\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolContainerRepository;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolRepository;
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
    public function iframe(
        $buffer,
        Database $database,
        RequestStack $requestStack,
        ParameterBag $parameterBag,
        CookieToolRepository $cookieToolRepository,
        string $sourceId,
        InsertTagParser $insertTagParser
    ) {

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
                        $return .= $this->getIframeHTML(
                            $iframeHTML,
                            $requestStack,
                            $database,
                            $parameterBag,
                            $cookieToolRepository,
                            $sourceId,
                            $insertTagParser
                        );
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
     * @param          $iframeHTML
     * @param          $requestStack
     * @param Database $database
     *
     * @return string
     * @throws \Exception
     */
    private function getIframeHTML(
        $iframeHTML,
        $requestStack,
        Database $database,
        ParameterBag $parameterBag,
        CookieToolRepository $cookieToolRepository,
        string $sourceId,
        InsertTagParser $insertTagParser
    ) {

        // Abmessungen des Block Container, damit es die gleiche Göße wie das iFrame hat.

        [$dataFromExternalMediaAndBar,$blockTexts,$size,$iconPath,$cookieTool] = Blocker::getIframeHTML(
            $iframeHTML,
            $requestStack,
            $database,
            $parameterBag,
            $cookieToolRepository,
            $sourceId,
            $insertTagParser,
        );

        $newBuffer = Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $size,
            $iframeHTML,
            $insertTagParser,
            $iconPath,
        );

        //User möchte das iFrame sehen, aber vielleicht auch über JS wieder blocken
        if (Blocker::isUserCookieDontAllowMedia($cookieTool)) {
            return $iframeHTML;
        } else {
            return $newBuffer;
        }
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