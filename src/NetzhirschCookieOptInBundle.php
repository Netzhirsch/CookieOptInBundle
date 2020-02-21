<?php

namespace Netzhirsch\CookieOptInBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Netzhirsch\CookieOptInBundle\DependencyInjection\Extension;

class NetzhirschCookieOptInBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new Extension();
    }
    
}