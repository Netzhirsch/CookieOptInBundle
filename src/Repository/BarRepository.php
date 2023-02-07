<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;
use Netzhirsch\CookieOptInBundle\Classes\GlobalDefaultText;

class BarRepository extends Repository
{

    public function __construct(Database $database)
    {
        parent::__construct($database);
    }

    public function findAll(): array
    {
        $strQuery = "SELECT id,pid,privacyPolicy FROM tl_ncoi_cookie";

        $founded = $this->findAllAssoc($strQuery, [],[]);
        if (empty($founded))
            return [];
        return $founded;
    }

    public function loadBlockContainerTexts($modId): array
    {
        $strQuery
            = "SELECT i_frame_video,i_frame_maps,i_frame_i_frame,i_frame_always_load,i_frame_load 
                FROM tl_ncoi_cookie WHERE pid = ? ";

        $founded = $this->findRow($strQuery,[], [$modId]);
        if (empty($founded)) {
            $globalDefaultText = new GlobalDefaultText();
            return $globalDefaultText->getAllAssoc();
        }

        return $founded;
    }

    /**
     * @param $ids
     * @return array|null
     */
    public function findByIds($ids)
    {
        if (!is_array($ids))
            return null;

        $strQuery = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid IN (?) LIMIT 1";

        return $this->findRow($strQuery,[], [implode(",",$ids)]);
    }


    public function findByLayoutOrPage($pageId)
    {
        $strQuery = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid IN (SELECT module FROM tl_content AS content LEFT JOIN tl_article AS article ON article.id = content.pid LEFT JOIN tl_page AS page ON page.id = article.pid WHERE page.id = ? AND content.module <> 0 OR page.pid = ? AND content.module <> 0)";

        $founded = $this->findAllAssoc($strQuery,[], [$pageId,$pageId]);
        if (empty($founded))
            return [];
        return $founded;
    }

    public function findByPid($id): array
    {
        $strQuery = "SELECT cookieGroups,cookieVersion,respectDoNotTrack FROM tl_ncoi_cookie WHERE pid = ?";

        $founded = $this->findRow($strQuery,[], [$id]);
        if (empty($founded))
            return [];

        return $founded;

    }
}