<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;

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
     * @throws DriverException
     * @throws DBALException
     */
    public function findByUrl($url) {
        $sql = "SELECT id,pid,cookieToolsSelect,cookieToolsProvider,cookieToolsPrivacyPolicyUrl,i_frame_blocked_text FROM tl_fieldpalette WHERE pfield = ? AND i_frame_blocked_urls LIKE ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, 'cookieTools');
        $stmt->bindValue(2, '%'.$url.'%');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param $type
     * @return mixed[]
     * @throws DBALException
     * @throws DriverException
     */
    public function findByType($type)
    {
        $sql = "SELECT id,pid,cookieToolsSelect,cookieToolsProvider,cookieToolsPrivacyPolicyUrl FROM tl_fieldpalette WHERE pfield = ? AND cookieToolsSelect = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, 'cookieTools');
        $stmt->bindValue(2, $type);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}