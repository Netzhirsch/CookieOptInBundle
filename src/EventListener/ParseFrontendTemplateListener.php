<?php


namespace Netzhirsch\CookieOptInBundle\EventListener;


class ParseFrontendTemplateListener
{
    public function onParseFrontendTemplate($buffer, $template)
    {
            if ($template == 'ce_html' && strpos($buffer,'<iframe') !== false) {
                $height = substr($buffer,strpos($buffer,'height'),11);
                $height = substr($height,8,3);

                $width = substr($buffer,strpos($buffer,'width'),11);
                $width = substr($width,7,3);

                $htmlContainer = '<div class="ncoi---blocked " style="height:'.$height.'px; width:'.$width.'px" >';
                $htmlContainerEnd = '</div>';

                $htmlConsentBox = '<div class="ncoi---consent-box">';
                $htmlConsentBoxEnd = '</div>';


                $htmlConsentLink = '<div class="ncoi---blocked-link"><a href="#" class="ncoi---release" title="erlauben">';
                $htmlConsentLinkEnd = 'laden</a></div>';

                $htmlIcon = '';
                $iconPath = 'bundles'.DIRECTORY_SEPARATOR.'netzhirschcookieoptin'.DIRECTORY_SEPARATOR;
                $htmlDisclaimer = '<div class="ncoi---blocked-disclaimer">';
                if (strpos($buffer,'www.youtube') !== false) {
                    $htmlDisclaimer .= 'Durch das Laden dieses Video stimmen Sie den Datenschutzbedingungen von YouTube LLC zu.';
                    $htmlIcon = '<div class="ncoi---blocked-icon"><img alt="youtube" src="'.$iconPath.'youtube-brands.svg"></div>';
                }
                $htmlDisclaimer .= '</div>';

                $htmlReleaseAll = '<label class="ncoi--release-all">Youtube immer laden<input type="checkbox"></label>';
                $iframe = '<script type="text/template">'.base64_encode($buffer).'</script>';

                $buffer = $htmlContainer.$htmlConsentBox.$htmlDisclaimer.$htmlConsentLink.$htmlIcon.$htmlConsentLinkEnd.$htmlReleaseAll.$htmlConsentBoxEnd.$iframe.$htmlContainerEnd;
            }
        return $buffer;
    }
}