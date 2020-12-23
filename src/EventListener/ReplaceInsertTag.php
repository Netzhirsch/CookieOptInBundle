<?php


namespace Netzhirsch\CookieOptInBundle\EventListener;



use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;

class ReplaceInsertTag
{
    /**
     * @param $insertTag
     * @return mixed
     * @throws DBALException
     * @throws Exception
     */
    public function onReplaceInsertTagsListener($insertTag)
    {
        global $objPage;
        if (empty($objPage))
            return $insertTag;

        if (PageLayoutListener::shouldRemoveModules($objPage)) {
            $modIdsInBuffer = PageLayoutListener::getModuleIdFromHtmlElement($insertTag);
            if (!empty($modIdsInBuffer)) {
                /** @var Connection $conn */
                /** @noinspection MissingService */
                $conn = System::getContainer()->get('database_connection');
                $barRepo = new BarRepository($conn);
                $return = $barRepo->findByIds($modIdsInBuffer);
                if (!empty($return)) {
                    $cookieBarId = $return['pid'];
                    return str_replace('{{insert_module::'.$cookieBarId.'}}','',$insertTag);
                }
            }
        }

        return $insertTag;
    }
}