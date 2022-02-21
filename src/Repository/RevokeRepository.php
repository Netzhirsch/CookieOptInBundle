<?php


namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;

class RevokeRepository extends Repository
{

    public function __construct(Database $database)
    {
        parent::__construct($database);
    }

    public function findByPid($pid){
        $strQuery = "SELECT id,pid FROM tl_ncoi_cookie_revoke WHERE pid = ?";
        return $this->findRow($strQuery,[], [$pid]);
    }
}