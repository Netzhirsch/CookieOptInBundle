<?php

namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Database;
use Exception;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;

class ReplaceInsertTag
{
    /** @var Database $database */
    private $database;

    public function __construct()
    {
        $this->database = Database::getInstance();;
    }

    /**
     * @param $insertTag
     * @return mixed
     * @throws Exception
     */
    public function onReplaceInsertTagsListener($insertTag): mixed
    {
        global $objPage;
        if (empty($objPage)) {
            return $insertTag;
        }

        if (PageLayoutListener::shouldRemoveModules($objPage)) {
            $modIdsInBuffer = PageLayoutListener::getModuleIdFromHtmlElement($insertTag);
            if (!empty($modIdsInBuffer)) {
                $conn = $this->database;
                $barRepo = new BarRepository($conn);
                $return = $barRepo->findByIds($modIdsInBuffer);
                if (!empty($return)) {
                    $cookieBarId = $return['pid'];

                    return str_replace('{{insert_module::'.$cookieBarId.'}}', '', $insertTag);

                }
            }
        }

        return $insertTag;
    }
}