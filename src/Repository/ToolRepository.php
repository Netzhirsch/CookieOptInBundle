<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;

class ToolRepository extends Repository
{

    public function __construct(Database $database)
    {
        parent::__construct($database);
    }

    /**
     * @param $url
     * @return mixed[]
     */
    public function findByUrl($url): array
    {
        $strQuery = "SELECT id,pid,cookieToolsSelect,cookieToolsProvider,cookieToolsPrivacyPolicyUrl,i_frame_blocked_text FROM tl_fieldpalette WHERE pfield = ? AND i_frame_blocked_urls LIKE ? AND i_frame_blocked_urls <> ?";

        $founded = $this->findAllAssoc($strQuery,[], [
            'cookieTools',
            '%'.$url.'%',
            ''
        ]);
        if (empty($founded))
            return [];

        return $founded;
    }

    /**
     * @param $type
     * @return mixed[]
     */
    public function findByType($type)
    {
        $strQuery = "SELECT id,pid,cookieToolsSelect,cookieToolsProvider,cookieToolsPrivacyPolicyUrl FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";

        $founded = $this->findAllAssoc($strQuery,[], [
            'cookieTools',
            $type,
        ]);
        if (empty($founded))
            return [];

        return $founded;
    }
}