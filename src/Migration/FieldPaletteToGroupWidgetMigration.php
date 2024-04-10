<?php

namespace Netzhirsch\CookieOptInBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;
use Netzhirsch\CookieOptInBundle\Entity\CookieToolContainer;
use Netzhirsch\CookieOptInBundle\Entity\OtherScript;
use Netzhirsch\CookieOptInBundle\Entity\OtherScriptContainer;

class FieldPaletteToGroupWidgetMigration extends AbstractMigration
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_fieldpalette'])) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {

        $strQuery = 'CREATE TABLE tl_ncoi_cookie_tool (id INT UNSIGNED AUTO_INCREMENT NOT NULL, parent INT NOT NULL, position INT UNSIGNED NOT NULL, cookieToolsName LONGTEXT DEFAULT NULL, cookieToolsSelect VARCHAR(255) DEFAULT NULL, cookieToolsTechnicalName VARCHAR(255) DEFAULT NULL, cookieToolsTrackingId VARCHAR(255) DEFAULT NULL, cookieToolsTrackingServerUrl VARCHAR(255) DEFAULT NULL, cookieToolsProvider VARCHAR(255) DEFAULT NULL, cookieToolsPrivacyPolicyUrl VARCHAR(255) DEFAULT NULL, cookieToolsUse LONGTEXT DEFAULT NULL, cookieToolGroup LONGTEXT DEFAULT NULL, cookieToolExpiredTime LONGTEXT DEFAULT NULL,`googleConsentMode` tinyint(1) NOT NULL DEFAULT 0, i_frame_blocked_urls LONGTEXT DEFAULT NULL, i_frame_blocked_text LONGTEXT DEFAULT NULL, INDEX IDX_712B124B3D8E604F (parent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC';
        $this->entityManager->getConnection()->executeQuery($strQuery);
        $strQuery = 'CREATE TABLE tl_ncoi_cookie_tool_container (id INT AUTO_INCREMENT NOT NULL, sourceId INT NOT NULL, sourceTable VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC';
        $this->entityManager->getConnection()->executeQuery($strQuery);
        $strQuery = 'CREATE TABLE tl_ncoi_other_script (id INT UNSIGNED AUTO_INCREMENT NOT NULL, parent INT NOT NULL, position INT UNSIGNED NOT NULL, cookieToolsName LONGTEXT DEFAULT NULL, cookieToolsTechnicalName VARCHAR(255) DEFAULT NULL, cookieToolsProvider VARCHAR(255) DEFAULT NULL, cookieToolsPrivacyPolicyUrl VARCHAR(255) DEFAULT NULL, cookieToolsUse LONGTEXT DEFAULT NULL, cookieToolGroup LONGTEXT DEFAULT NULL, cookieToolExpiredTime LONGTEXT DEFAULT NULL, cookieToolsCode LONGTEXT DEFAULT NULL, INDEX IDX_933D0543D8E604F (parent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC';
        $this->entityManager->getConnection()->executeQuery($strQuery);
        $strQuery = 'CREATE TABLE tl_ncoi_other_script_container (id INT AUTO_INCREMENT NOT NULL, sourceId INT NOT NULL, sourceTable VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC';
        $this->entityManager->getConnection()->executeQuery($strQuery);
        $strQuery = 'ALTER TABLE tl_ncoi_cookie_tool ADD CONSTRAINT FK_712B124B3D8E604F FOREIGN KEY (parent) REFERENCES tl_ncoi_cookie_tool_container (id)';
        $this->entityManager->getConnection()->executeQuery($strQuery);
        $strQuery = 'ALTER TABLE tl_ncoi_other_script ADD CONSTRAINT FK_933D0543D8E604F FOREIGN KEY (parent) REFERENCES tl_ncoi_other_script_container (id)';
        $this->entityManager->getConnection()->executeQuery($strQuery);

        $strQuery = 'SELECT *
                FROM tl_fieldpalette ORDER BY sorting';

        $result = $this->entityManager->getConnection()->executeQuery($strQuery);
        $fieldPalettes = $result->fetchAllAssociative();
        $newCookieToolContainers = [];
        $newOtherScriptContainers = [];
        foreach ($fieldPalettes as $key => $fieldPalette) {
            if (!in_array($fieldPalette['pid'], $newCookieToolContainers) && $fieldPalette['pfield'] == 'cookieTools')
                $newCookieToolContainers[] = $fieldPalette['pid'];
            if (!in_array($fieldPalette['pid'], $newOtherScriptContainers) && $fieldPalette['pfield'] == 'otherScripts')
                $newOtherScriptContainers[] = $fieldPalette['pid'];
            $fieldPalette['sorting'] = $key;
        }
        $cookieToolContainers = [];
        foreach ($newCookieToolContainers as $newCookieToolContainer) {
            $cookieToolContainer = new CookieToolContainer();
            $cookieToolContainer->setSourceId($newCookieToolContainer);
            $cookieToolContainer->setSourceTable('tl_module');
            $this->entityManager->persist($cookieToolContainer);
            $cookieToolContainers[] = $cookieToolContainer;
        }
        $otherScriptContainers = [];
        foreach ($newCookieToolContainers as $newCookieToolContainer) {
            $otherScriptContainer = new OtherScriptContainer();
            $otherScriptContainer->setSourceId($newCookieToolContainer);
            $otherScriptContainer->setSourceTable('tl_module');
            $this->entityManager->persist($otherScriptContainer);
            $otherScriptContainers[] = $otherScriptContainer;
        }
        foreach ($fieldPalettes as $fieldPalette) {
            foreach ($cookieToolContainers as $cookieToolContainer) {
                if ($fieldPalette['pid'] == $cookieToolContainer->getSourceId() && $fieldPalette['pfield'] == 'cookieTools') {
                    $cookieTool = new CookieTool();
                    $cookieTool->setPosition($fieldPalette['sorting']);
                    $cookieTool->setParent($cookieToolContainer);
                    $cookieTool->setCookieToolsName($fieldPalette['cookieToolsName']);
                    $cookieTool->setCookieToolsSelect($fieldPalette['cookieToolsSelect']);
                    $cookieTool->setCookieToolsTechnicalName($fieldPalette['cookieToolsTechnicalName']);
                    $cookieTool->setCookieToolsTrackingId($fieldPalette['cookieToolsTrackingId']);
                    $cookieTool->setCookieToolsTrackingServerUrl($fieldPalette['cookieToolsTrackingServerUrl']);
                    $cookieTool->setCookieToolsProvider($fieldPalette['cookieToolsProvider']);
                    $cookieTool->setCookieToolsPrivacyPolicyUrl($fieldPalette['cookieToolsPrivacyPolicyUrl']);
                    $cookieTool->setCookieToolsUse($fieldPalette['cookieToolsUse']);
                    $cookieTool->setCookieToolGroup($fieldPalette['cookieToolGroup']);
                    $cookieTool->setCookieToolExpiredTime($fieldPalette['cookieToolExpiredTime']);
                    $cookieTool->setIFrameBlockedUrls($fieldPalette['i_frame_blocked_urls']);
                    $cookieTool->setIFrameBlockedText($fieldPalette['i_frame_blocked_text']);
                    $this->entityManager->persist($cookieTool);
                }
            }
            foreach ($otherScriptContainers as $otherScriptContainer) {
                if ($fieldPalette['pid'] == $otherScriptContainer->getSourceId() && $fieldPalette['pfield'] == 'otherScripts') {
                    $otherScript = new OtherScript();
                    $otherScript->setPosition($fieldPalette['sorting']);
                    $otherScript->setParent($otherScriptContainer);
                    $otherScript->setCookieToolsName($fieldPalette['cookieToolsName']);
                    $otherScript->setCookieToolsTechnicalName($fieldPalette['cookieToolsTechnicalName']);
                    $otherScript->setCookieToolsProvider($fieldPalette['cookieToolsProvider']);
                    $otherScript->setCookieToolsPrivacyPolicyUrl($fieldPalette['cookieToolsPrivacyPolicyUrl']);
                    $otherScript->setCookieToolsUse($fieldPalette['cookieToolsUse']);
                    $otherScript->setCookieToolGroup($fieldPalette['cookieToolGroup']);
                    $otherScript->setCookieToolExpiredTime($fieldPalette['cookieToolExpiredTime']);
                    $otherScript->setCookieToolsCode($fieldPalette['cookieToolsCode']);
                    $this->entityManager->persist($otherScript);
                }
            }
        }
        $this->entityManager->flush();


        $strQuery = 'CREATE TABLE ncoi_fieldpalette_backup LIKE tl_fieldpalette;';

        $this->entityManager->getConnection()->executeQuery($strQuery);

        $strQuery = 'INSERT INTO ncoi_fieldpalette_backup SELECT * FROM tl_fieldpalette;;';

        $this->entityManager->getConnection()->executeQuery($strQuery);

        $strQuery = 'DROP TABLE tl_fieldpalette';

        $this->entityManager->getConnection()->executeQuery($strQuery);

        return $this->createResult(
            true,
            'Inhalt von tl_fieldpalette in tl_ncoi_cookie_tool_container,tl_ncoi_cookie_tool,'
            .'tl_ncoi_other_script_container und tl_ncoi_other_script überführt.'
            .'Tabelle tl_fieldpalette nach tl_fieldpalette_backup kopiert und gelöscht.'
        );
    }
}