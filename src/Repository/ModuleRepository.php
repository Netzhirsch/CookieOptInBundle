<?php


namespace Netzhirsch\CookieOptInBundle\Repository;


use Doctrine\DBAL\Connection;

class ModuleRepository
{
    /** @var Connection $conn */
    private $conn;
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findByIds($modIds)
    {
        $sql = "SELECT html FROM tl_module WHERE type = 'html' AND id IN (".implode(",",$modIds).")";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}