<?php


namespace Netzhirsch\CookieOptInBundle\Repository;


use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Logger\DatabaseExceptionLogger;

class ModuleRepository
{
    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findByIds($modIds): array
    {
        $sql = "SELECT html FROM tl_module WHERE type = 'html' AND id IN (".implode(",",$modIds).")";
        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);
        if (empty($stmt))
            return [];

        DatabaseExceptionLogger::tryExecute($stmt);

        $result = DatabaseExceptionLogger::tryFetch($stmt);
        if (empty($result))
            return [];

        return $result;
    }
}