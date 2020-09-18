<?php


namespace Netzhirsch\CookieOptInBundle\EventListener;



use Doctrine\DBAL\DBALException;

class ReplaceInsertTag
{
    /**
     * @param $insertTag
     * @return mixed
     * @throws DBALException
     */
    public function onReplaceInsertTagsListener($insertTag)
    {
        if ($_SESSION['nh_remove_modules']) {
            $modIdsInBuffer = PageLayoutListener::getModuleIdFromHtmlElement($insertTag);
            if (!empty($modIdsInBuffer)) {
                $return = PageLayoutListener::findCookieModuleByPid($modIdsInBuffer);
                if (!empty($return)) {
                    $cookieBarId = $return['pid'];
                    $insertTag = str_replace('{{insert_module::'.$cookieBarId.'}}','',$insertTag);
                }
            }
        }

        return $insertTag;
    }
}