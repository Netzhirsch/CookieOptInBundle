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
        $strQuery = "SELECT html FROM tl_module WHERE type = 'html' AND id IN (?)";

        $founded = $this->findAllAssoc($strQuery,[], [implode(",",$modIds)]);
        if (empty($founded))
            return [];
        return $founded;
    }
}