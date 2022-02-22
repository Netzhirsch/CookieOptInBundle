<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Contao\Database;
use Contao\DC_Table;
use Doctrine\DBAL\Exception\SyntaxErrorException;

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
        $founded = $result->fetchAssoc();
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

    public function updateOrInsert(DC_Table $dca,string $table,$value,$pid = null,$field = null) {
        $conn = $dca->Database;
        $repo = new Repository($conn);
        $strQuerySelect = "SELECT id FROM ".$table." WHERE pid= ?";
        if (empty($pid))
            $pid = $dca->__get('id');
        $result = $repo->findRow($strQuerySelect,[], [$pid]);
        $id = $result['id'];
        if (empty($field))
            $field = $dca->__get('field');

        $set = [];
        if (empty($id)) {
            $strQueryUpdateInsert = "INSERT ".$table." %s";
            $set['pid'] = $pid;
            $this->executeStatement($strQueryUpdateInsert,$set,[]);
        }
        $set = [$field => $value];
        $strQueryUpdateInsert = "UPDATE ".$table." %s WHERE pid = ?";
        $this->executeStatement($strQueryUpdateInsert,$set,[$pid]);

        return null;
    }

    public function executeStatement(string $strQuery,array $set,array $conditions) {
        $conn = $this->database;
        $stmt = $conn->prepare($strQuery);
        if (count($set) > 0)
            $stmt = $stmt->set($set);
        try {
            if (count($conditions) > 0)
                return $stmt->execute($conditions);
            return $stmt->execute();
        } catch (SyntaxErrorException $exception) {
            dump($exception);
            dd($strQuery,$set,$conditions);
        }

    }

}