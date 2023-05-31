<?php


namespace Netzhirsch\CookieOptInBundle\Logger;


use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use Psr\Log\LogLevel;

class Logger
{
    public static function logExceptionInContaoSystemLog($message): void
    {

        /** @var \Symfony\Bridge\Monolog\Logger $logger */
        $logger = System::getContainer()->get('monolog.logger.contao');

        $logger->log(
            LogLevel::ERROR, $message,
            [
                'contao' => new ContaoContext(__CLASS__.'::'.__FUNCTION__)
            ]
        );
    }
}