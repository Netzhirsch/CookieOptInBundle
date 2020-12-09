<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;

class LayoutRepository
{
    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function find($id)
    {
        $sql = "SELECT analytics FROM tl_layout WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}