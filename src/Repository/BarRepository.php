<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

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

    public function loadBlockContainerTexts($modId) {

        $sql
            = "SELECT i_frame_video,i_frame_maps,i_frame_i_frame,i_frame_always_load,i_frame_load 
                FROM tl_ncoi_cookie WHERE pid = ?";
        /** @var Statement $stmt */
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function findByIds($ids){
        $sql = "SELECT * FROM tl_ncoi_cookie WHERE pid IN (".implode(",",$ids).") LIMIT 1";
        /** @var Statement $stmt */
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }
}