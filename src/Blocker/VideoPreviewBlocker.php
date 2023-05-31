<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Database;
use Contao\LayoutModel;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Netzhirsch\CookieOptInBundle\Resources\contao\Classes\DataFromExternalMediaAndBar;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\CookieToolRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class VideoPreviewBlocker
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
    )
    {

        if (empty($requestStack)) {
            return $buffer;
        }

        $start = strpos($buffer, '<figure');
        if ($start == 0) {
            return $buffer;
        }

        $noIframe = substr($buffer, 0, $start);
        $iframe = substr($buffer, $start);
        $end = strpos($iframe, '</figure>')+ strlen('</figure>');
        $iframe = substr($iframe, 0, $end);
        $iframe = $this->getIframeHTML(
            $iframe,
            $requestStack,
            $database,
            $parameterBag,
            $cookieToolRepository,
            $sourceId,
            $insertTagParser
        );
        $nextPart = substr($buffer, $start+$end);
        if (strpos($nextPart, '<figure') > 0 && !strpos($nextPart, 'ncoi---blocked')) {
            $nextPart .= $this->iframe(
                $nextPart,
                $database,
                $requestStack,
                $parameterBag,
                $cookieToolRepository,
                $sourceId,
                $insertTagParser
            );
        }
        return $noIframe.$iframe.$nextPart;
    }


    /**
     * @param string $html
     * @param $requestStack
     * @param Database $database
     * @return string
     */
    private function getIframeHTML(
        $html,
        $requestStack,
        $database,
        ParameterBag $parameterBag,
        CookieToolRepository $cookieToolRepository,
        string $sourceId,
        InsertTagParser $insertTagParser
    )
    {
        [$dataFromExternalMediaAndBar,$blockTexts,$sizeBackground,$iconPath,$cookieTool] = Blocker::getIframeHTML(
            $html,
            $requestStack,
            $database,
            $parameterBag,
            $cookieToolRepository,
            $sourceId
        );

        // Abmessungen des Block Container, damit es die gleiche Göße wie das iFrame hat.
        $imageSrc = $this->getImageSrc($html);

        $innerFigure = substr($html, strpos($html, '<figure'));
        $innerFigure = substr($innerFigure, 0, strpos($innerFigure, '</figure>'));
        $sizeIframe = $this->getIframeSize($innerFigure);
        $html = $this->replacePreviewImageWithIframe($html, $sizeIframe);

        $newBuffer = Blocker::getHtmlContainer(
            $dataFromExternalMediaAndBar,
            $blockTexts,
            $sizeIframe,
            $html,
            $insertTagParser,
            $iconPath
        );

        if (!empty($sizeBackground['height']) && !Blocker::hasUnit($sizeBackground['height'])) {
            $sizeBackground['height'] .= 'px';
        }
        if (!empty($sizeBackground['width']) && !Blocker::hasUnit($sizeBackground['width'])) {
            $sizeBackground['width'] .= 'px';
        }

        if (!empty($imageSrc)) {
            $search = 'style="';
            $replace = $search.' background-image:url('.$imageSrc.');
                    background-repeat: no-repeat;
                    background-position: center;';
            if (!empty($sizeBackground['width']) || !empty($sizeBackground['height'])) {
                $replace .= 'background-size: '.$sizeBackground['width'].' '.$sizeBackground['height'].';';
            }

            $newBuffer = str_replace($search, $replace, $newBuffer);
        }

        //User möchte das iFrame sehen, aber vielleicht auch über JS wieder blocken
        if (Blocker::isUserCookieDontAllowMedia($cookieTool)) {
            return $html;
        } else {
            return $newBuffer;
        }
    }

    private function replacePreviewImageWithIframe(
        string $html,
        array $size
    )
    {
        if (strpos($html, '<img') === false) {
            return $html;
        }
        $src = self::getFullURL($html);
        return '<iframe src="'.$src.'" allowfullscreen="" width="'.$size['width'].'" height="'.$size['height'].'"></iframe>';
    }

    private function getImageSrc(string $html)
    {
        $imageSrc = '';
        if (strpos($html, '<img') !== false) {
            $imageSrc = substr($html, strpos($html, 'src="'));
            $imageSrc = str_replace('src="', '', $imageSrc);
            $imageSrc = substr($imageSrc, 0, strpos($imageSrc, '"'));
        }

        return $imageSrc;
    }


    private static function getFullURL(string $html)
    {
        $html = substr($html, strpos($html, '<iframe') );
        $htmlUrlPart = substr($html, strpos($html, 'src="'));
        $htmlUrlPart = str_replace('src="', '', $htmlUrlPart);
        $htmlUrlPart = str_replace('www.', '', $htmlUrlPart);

        return substr($htmlUrlPart, 0, strpos($htmlUrlPart, '"'));

    }

    private function getIframeSize($html): array
    {
        $size = [];
        $size['width'] = $this->getDimensionFromIframeOrImage($html,'width',1920);
        $size['height'] = $this->getDimensionFromIframeOrImage($html,'height',1080);
        return $size;
    }

    private function getDimensionFromIframeOrImage(string $html,string $dimension,int $dimensionValue): float|int|string
    {
        $iframe = substr($html, strpos($html, '<iframe') );
        $htmlUrlPart = substr($iframe, strpos($iframe, $dimension.'="'));
        $htmlUrlPart = str_replace($dimension.'="', '', $htmlUrlPart);
        $htmlUrlPart = substr($htmlUrlPart, 0,strpos($htmlUrlPart, '"') );
        if (!empty($htmlUrlPart) && is_numeric($htmlUrlPart)) {
            $dimensionValue = $htmlUrlPart;
        } else {
            $iframe = substr($html, strpos($html, '<img') );
            $htmlUrlPart = substr($iframe, strpos($iframe, $dimension.'="'));
            $htmlUrlPart = str_replace($dimension.'="', '', $htmlUrlPart);
            $htmlUrlPart = substr($htmlUrlPart, 0,strpos($htmlUrlPart, '"') );
            if (!empty($htmlUrlPart) && is_numeric($htmlUrlPart)) {
                $dimensionValue = $htmlUrlPart;
            }
        }
        return $dimensionValue;
    }

}