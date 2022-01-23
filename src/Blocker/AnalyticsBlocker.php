<?php


namespace Netzhirsch\CookieOptInBundle\Blocker;


class AnalyticsBlocker
{
    public function analyticsTemplate($buffer,$analyticsType)
    {
        if (empty($buffer))
            return $buffer;
        //class hinzufügen damit die in JS genutzt werden kann
        $buffer = str_replace('<script','<script class="analytics-decoded-'.$analyticsType.'"',$buffer);
        return '<script id="analytics-encoded-'.$analyticsType.'"><!-- '.base64_encode($buffer).' --></script>';
    }
}