<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Logger\DatabaseExceptionLogger;

class LayoutRepository
{
    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function find($id): array
    {
        $sql = "SELECT analytics FROM tl_layout WHERE id = ?";
        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);
        if (empty($stmt))
            return [];

        $stmt->bindValue(1, $id);

        DatabaseExceptionLogger::tryExecute($stmt);
        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (empty($result))
            return [];

        return $result;
    }
}