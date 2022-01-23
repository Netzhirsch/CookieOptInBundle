<?php


use PHPUnit\Framework\TestCase;
use Netzhirsch\CookieOptInBundle\Blocker\AnalyticsBlocker;

class AnalyticsBlockerTest extends TestCase
{
    public function testEncodeEmptyAnalyticsTemplate()
    {
        $analyticsBlocker = new AnalyticsBlocker();
        $template = $analyticsBlocker->analyticsTemplate('', '');
        $this->assertEquals('', $template);
    }

    /**
     * @dataProvider analyticTemplatesProvider
     */
    public function testEncodeGoogleAnalyticsTemplate
    (
        string $analyticsTypeFileName,
        string $analyticsType,
        bool $custom
    )
    {
        $filename = dirname(__DIR__,5).DIRECTORY_SEPARATOR;
        if ($custom) {
            $filename .= 'templates';
        } else {
            $filename .=
                'vendor'.
                DIRECTORY_SEPARATOR.
                'contao'.
                DIRECTORY_SEPARATOR.
                'core-bundle'.
                DIRECTORY_SEPARATOR.
                'src'.
                DIRECTORY_SEPARATOR.
                'Resources'.
                DIRECTORY_SEPARATOR.
                'contao'.
                DIRECTORY_SEPARATOR.
                'templates'.
                DIRECTORY_SEPARATOR.
                'analytics'
            ;
        }
        $filename .= DIRECTORY_SEPARATOR.$analyticsTypeFileName.'.html5';

        if (!file_exists($filename))
            $this->fail('Folgende Datei nicht gefunden: '.$filename);

        $content = file_get_contents($filename);
        $buffer = str_replace('<script','<script class="analytics-decoded-'.$analyticsType.'"',$content);
        $expected = '<script id="analytics-encoded-'.$analyticsType.'"><!-- '.base64_encode($buffer).' --></script>';

        $analyticsBlocker = new AnalyticsBlocker();

        $template = $analyticsBlocker->analyticsTemplate($content, $analyticsType);
        $this->assertEquals($expected, $template,'Fehler in: '.$analyticsType);
    }

    public function analyticTemplatesProvider(): array
    {
        return [
            [
                'analytics_google',
                'google',
                true
            ],
            [
                'analytics_google',
                'google',
                false
            ],
            [
                'analytics_matomo',
                'matomo',
                true
            ],
            [
                'analytics_matomo',
                'matomo',
                false
            ]
        ]
            ;
    }
}
