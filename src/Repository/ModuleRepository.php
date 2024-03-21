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
		if (!empty($modIds)) {
			$strQuery = "SELECT html FROM tl_module WHERE type = 'html' AND id IN (".implode(',',$modIds).")";
			$found = $this->findAllAssoc($strQuery,[], []);
			$strQuery2 = "SELECT unfilteredHtml as html FROM tl_module WHERE type = 'unfiltered_html' AND id IN (".implode(',',$modIds).")";
			$found2 = $this->findAllAssoc($strQuery2,[], []);
		}
		$result = array_merge($found ?? [], $found2 ?? []);
        if (empty($result))
            return [];
        return $result;
    }
}