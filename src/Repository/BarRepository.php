<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;

class BarRepository
{
    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findAll()
    {
        $sql = "SELECT id,pid,privacyPolicy FROM tl_ncoi_cookie ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}