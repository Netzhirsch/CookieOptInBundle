<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;

use Netzhirsch\CookieOptInBundle\Logger\DatabaseExceptionLogger;
use Netzhirsch\CookieOptInBundle\Classes\GlobalDefaultText;

class BarRepository
{
    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findAll(): array
    {
        $sql = "SELECT id,pid,privacyPolicy FROM tl_ncoi_cookie ";
        $stmt = null;

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);

        if (empty($stmt))
            return [];

        DatabaseExceptionLogger::tryExecute($stmt);

        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (!empty($result))
            return $result;

        return [];
    }

    public function loadBlockContainerTexts($modId): array
    {
        $sql
            = "SELECT i_frame_video,i_frame_maps,i_frame_i_frame,i_frame_always_load,i_frame_load 
                FROM tl_ncoi_cookie WHERE pid = ? ";

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);

        $globalDefaultText = new GlobalDefaultText();
        if (empty($stmt))
            return $globalDefaultText->getAllAssoc();

        $stmt->bindValue(1, $modId);

        DatabaseExceptionLogger::tryExecute($stmt);

        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (!empty($result))
            return $result;


        return $globalDefaultText->getAllAssoc();
    }

    public function findByIds($ids): array
    {

        $sql = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid IN (".implode(",",$ids).") LIMIT 1";

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);

        if (empty($stmt))
            return [];

        DatabaseExceptionLogger::tryExecute($stmt);

        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (!empty($result))
            return $result;

        return [];
    }


    public function findByLayoutOrPage($pageId)
    {
        $sql = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid IN (SELECT module FROM tl_content AS content LEFT JOIN tl_article AS article ON article.id = content.pid LEFT JOIN tl_page AS page ON page.id = article.pid WHERE page.id = ? AND content.module <> 0 OR page.pid = ? AND content.module <> 0)";

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);

        $stmt->bindValue(1, $pageId);
        $stmt->bindValue(2, $pageId);

        if (empty($stmt))
            return [];

        DatabaseExceptionLogger::tryExecute($stmt);

        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (!empty($result))
            return $result;

        return [];
    }

    public function findByPid($id): array
    {
        $sql = "SELECT cookieGroups,cookieVersion,respectDoNotTrack FROM tl_ncoi_cookie WHERE pid = ?";

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);

        $stmt->bindValue(1, $id);

        if (empty($stmt))
            return [];

        DatabaseExceptionLogger::tryExecute($stmt);

        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (!empty($result))
            return $result;

        return [];

    }

}