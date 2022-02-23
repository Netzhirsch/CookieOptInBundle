<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;

class LayoutRepository extends Repository
{

    public function __construct(Database $database)
    {
        parent::__construct($database);
    }

    public function find($id): array
    {
        $strQuery = "SELECT analytics FROM tl_layout WHERE id = ?";
        $founded = $this->findRow($strQuery,[], [$id]);
        if (empty($founded))
            return [];

        return $founded;
    }
}