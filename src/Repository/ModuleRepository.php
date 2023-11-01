<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;

class ModuleRepository extends Repository
{

    public function __construct(Database $database)
    {
        parent::__construct($database);
    }

    public function findByIds($modIds): array
    {
        $modIds = array_filter($modIds);
        $strQuery = "SELECT html FROM tl_module WHERE type = 'html' AND id IN (".implode(',',$modIds).")";
        $founded = $this->findAllAssoc($strQuery,[], []);
        if (empty($founded))
            return [];
        return $founded;
    }
}