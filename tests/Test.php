<?php


use PHPUnit\Framework\TestCase;
use Netzhirsch\CookieOptInBundle\NetzhirschCookieOptInBundle;

class Test extends TestCase
{
    public function testBundleFileExist()
    {
        $this->assertEquals('Netzhirsch\CookieOptInBundle\NetzhirschCookieOptInBundle', NetzhirschCookieOptInBundle::class);
    }
}
