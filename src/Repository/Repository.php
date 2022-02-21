<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;

class Repository
{

    /** @var Database $database */
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function findRow(string $strQuery,array $set,array $conditions)
    {
        $result = $this->executeStatement($strQuery,$set,$conditions);

        $founded = $result->fetchRow();
        if (!$founded || count($founded) == 0)
            return null;

        return $founded;
    }

    public function findAllAssoc(string $strQuery,array $set,array $conditions)
    {
        $result = $this->executeStatement($strQuery,$set,$conditions);
        $founded = $result->fetchAllAssoc();
        if (count($founded) == 0)
            return null;
        return $founded;
    }

    public function executeStatement(string $strQuery,array $set,array $conditions) {
        $conn = $this->database;
        $stmt = $conn->prepare($strQuery);
        if (count($set) > 0)
            $stmt = $stmt->set($set);
        if (count($conditions) > 0)
            return $stmt->execute($conditions);

        return $stmt->execute();
    }
}