<?php


namespace Netzhirsch\CookieOptInBundle\Logger;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Statement;

class DatabaseExceptionLogger
{

    /**
     * @param $sql
     * @param $conn
     * @return Statement
     * @noinspection PhpMissingReturnTypeInspection can not set return type to null or Statement in php 7.0
     */
    public static function tryPrepare($sql,Connection $conn)
    {
        try {
            return $conn->prepare($sql);
        } catch (Exception $e) {
            Logger::logExceptionInContaoSystemLog($e->getMessage());
        }
        return null;
    }

    public static function tryExecute($stmt)
    {
        try {
            $stmt->execute();
        } catch (SyntaxErrorException $e) {
            Logger::logExceptionInContaoSystemLog($e->getMessage());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            Logger::logExceptionInContaoSystemLog($e->getMessage());
        }
    }

    public static function tryFetch($stmt): array
    {
        try {
            return $stmt->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            Logger::logExceptionInContaoSystemLog($e->getMessage());
        }
        return [];
    }

    public static function tryFetchAssociative(Statement $stmt): array
    {
        try {
            return $stmt->fetchAssociative();
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            Logger::logExceptionInContaoSystemLog($e->getMessage());
        }
        return [];
    }


}