<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Logger\DatabaseExceptionLogger;

class ToolRepository
{

    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param $url
     * @return mixed[]
     */
    public function findByUrl($url): array
    {
        $sql = "SELECT id,pid,cookieToolsSelect,cookieToolsProvider,cookieToolsPrivacyPolicyUrl,i_frame_blocked_text FROM tl_fieldpalette WHERE pfield = ? AND i_frame_blocked_urls LIKE ? AND i_frame_blocked_urls <> ?";

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);
        if (empty($stmt))
            return [];


        $stmt->bindValue(1, 'cookieTools');
        $stmt->bindValue(2, '%'.$url.'%');
        $stmt->bindValue(3, '');
        DatabaseExceptionLogger::tryExecute($stmt);

        return DatabaseExceptionLogger::tryFetch($stmt);
    }

    /**
     * @param $type
     * @return mixed[]
     */
    public function findByType($type)
    {
        $sql = "SELECT id,pid,cookieToolsSelect,cookieToolsProvider,cookieToolsPrivacyPolicyUrl FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);
        if (empty($stmt))
            return [];

        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);
        if (empty($stmt))
            return [];

        $stmt->bindValue(1, 'cookieTools');
        $stmt->bindValue(2, $type);
        DatabaseExceptionLogger::tryExecute($stmt);

        return DatabaseExceptionLogger::tryFetch($stmt);
    }
}