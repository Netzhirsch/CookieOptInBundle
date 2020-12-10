<?php


namespace Netzhirsch\CookieOptInBundle\Repository;


use Doctrine\DBAL\Connection;
use Netzhirsch\CookieOptInBundle\Logger\DatabaseExceptionLogger;

class RevokeRepository
{
    /** @var Connection */
    private $conn;

    /**
     * RevokeRepository constructor.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findByPid($pid){
        $sql = "SELECT id,pid FROM tl_ncoi_cookie_revoke WHERE pid = ?";
        $stmt = DatabaseExceptionLogger::tryPrepare($sql,$this->conn);
        if (empty($stmt))
            return [];

        $stmt->bindValue(1, $pid);

        DatabaseExceptionLogger::tryExecute($stmt);

        return DatabaseExceptionLogger::tryFetch($stmt);
    }
}