<?php

namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Config;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use Exception;
use Netzhirsch\CookieOptInBundle\Controller\CookieController;
use Netzhirsch\CookieOptInBundle\Controller\LicenseController;
use Netzhirsch\CookieOptInBundle\Repository\ModuleRepository;

class PageLayoutListener {

    /**
     * @param PageModel $pageModel
     * @param LayoutModel $layout
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function onGetPageLayoutListener(PageModel $pageModel, LayoutModel $layout) {

        $removeModules = $this->shouldRemoveModules($pageModel);
        $moduleIds = [];
        $return = self::checkModules($layout, $removeModules, $moduleIds);
        $allModuleIdsInLayout = $return['allModuleIds'];
        $moduleIds = $return['moduleIds'];
        if (!empty($return['tlCookieIds']))
            $tlCookieIds[] = $return['tlCookieIds'];
        $return = self::checkModules($pageModel, $removeModules, $moduleIds);
        $moduleIds = $return['moduleIds'];
        if (empty($moduleIds)) {
            // remove in done on onParseFrontendTemplate event listener
            if (!$removeModules) {
                $return = self::getModuleIdFromInsertTag($pageModel,$layout,$allModuleIdsInLayout);
                if (!empty($return['moduleIds']))
                    $moduleIds = $return['moduleIds'];
            }
        }
        if (!empty($return['tlCookieIds']))
            $tlCookieIds[] = $return['tlCookieIds'];

        if ($removeModules) {
            return;
        }

        //for customer info
        if (empty($moduleIds)) {
            $GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInError.js|static';

            return;
        }
        if (!empty($tlCookieIds) && count($tlCookieIds) > 2) {
            $GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInErrorMore.js|static';

            return;
        }

        //module in this layout
        if (is_array($moduleIds))
            $modId = $moduleIds[0];
        else
            $modId = $moduleIds;

        /********* update groups for a version < 1.3.0 ************************************************************/
        $conn = System::getContainer()->get('database_connection');
        $sql = "SELECT cookieGroups,cookieVersion,respectDoNotTrack FROM tl_ncoi_cookie WHERE pid = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->execute();
        $result = $stmt->fetch();
        if (!$result)
            return;

        $cookieGroups = StringUtil::deserialize($result['cookieGroups']);
        if (!is_array($cookieGroups[0])) {
            $newValues = [];
            $key = 1;
            foreach ($cookieGroups as $cookieGroup) {
                $newValues[] = [
                    'key' => $key++,
                    'value' => $cookieGroup
                ];
            }
            $sql = "UPDATE tl_ncoi_cookie SET cookieGroups = ? WHERE pid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, serialize($newValues));
            $stmt->bindValue(2, $modId);
            $stmt->execute();
        }

        if (self::doNotTrackBrowserSetting($result['respectDoNotTrack']))
            CookieController::deleteCookies();
    }

    public static function doNotTrackBrowserSetting($respectDoNotTrack,$moduleId = null) {
        $doNotTrack = false;
        if (empty($respectDoNotTrack)) {
            $conn = System::getContainer()->get('database_connection');
            $sql = "SELECT respectDoNotTrack FROM tl_ncoi_cookie WHERE pid = ?";
            /** @var Statement $stmt */
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $moduleId);
            $stmt->execute();
            $respectDoNotTrack = $stmt->fetchColumn();
        }
        if (
            array_key_exists('HTTP_DNT', $_SERVER) && (1 === (int) $_SERVER['HTTP_DNT']) && $respectDoNotTrack

        ) {
            $doNotTrack = true;
            CookieController::deleteCookies();
        }

        return $doNotTrack;
    }

    /**
     * @param string $licenseKey
     * @param string $licenseExpiryDate
     *
     * @param        $domain
     *
     * @return bool
     * @throws Exception
     */
    public static function checkLicense($licenseKey,$licenseExpiryDate,$domain) {

        if (empty($licenseKey) || empty($licenseExpiryDate))
            return false;

        // in Frontend Y-m-d in Backend d.m.Y
        $licenseExpiryDate = date("Y-m-d", strtotime($licenseExpiryDate));
        if ($licenseExpiryDate < date("Y-m-d"))
            return false;

        return self::checkHash($licenseKey, $licenseExpiryDate, $domain);
    }

    /**
     * @param       $licenseKey
     * @param       $licenseExpiryDate
     * @param       $domain
     *
     * @return bool
     */
    private static function checkHash($licenseKey, $licenseExpiryDate, $domain) {
        $hashes[] = LicenseController::getHash($domain, $licenseExpiryDate);

        //all possible subdomains
        $domainLevels = explode(".", $domain);

        foreach ($domainLevels as $key => $domainLevel) {
            if (count($domainLevels) < 2)
                break;
            unset($domainLevels[$key]);
            $domain = implode(".", $domainLevels);
            $hashes[] = LicenseController::getHash($domain, $licenseExpiryDate);
        }

        if (in_array($licenseKey, $hashes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param DateTime $licenseExpiryDate
     *
     * @return DateInterval|false
     * @throws Exception
     */
    public static function getLicenseRemainingExpiryDays(DateTime $licenseExpiryDate) {

        $today = new DateTime('now');

        return date_diff($licenseExpiryDate, $today);
    }

    /**
     * @return DateInterval|false|null
     * @throws Exception
     */
    public static function checkLicenseRemainingTrialPeriod(){
        $dateInterval = null;

        $path = dirname(__DIR__);
        $filename = $path.DIRECTORY_SEPARATOR.'NetzhirschCookieOptInBundle.php';
        if (file_exists($filename)) {
            $fileTime = self::getTrialPeriod();
            if ($fileTime->getTimestamp() > time()) {
                $today = new DateTime();
                $dateInterval = date_diff($fileTime, $today);
            }
        }
        // Only for testing
//		$dateInterval = null;
        return $dateInterval;
    }

    /**
     * @return DateTime|null
     * @throws Exception
     */
    public static function getTrialPeriod(){

        $datetimeFile = null;

        $path = dirname(__DIR__);
        $filename = $path.DIRECTORY_SEPARATOR.'NetzhirschCookieOptInBundle.php';
        if (file_exists($filename)) {
            $fileTime = strtotime('+1 month',filemtime($filename));
            $datetimeFile = new DateTime();
            $datetimeFile->setTimestamp($fileTime);
        }
        return $datetimeFile;
    }

    /**
     * @param PageModel $page
     * @param LayoutModel $layout
     * @param $allModuleIds
     * @return mixed[]
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getModuleIdFromInsertTag($page,LayoutModel $layout,$allModuleIds = null)
    {
        $parameters = [
            'moduleIds' => null,
            'tlCookieIds' => null
        ];
        $id = $page->__get('id');
        /** @var Connection $conn */
        $conn = System::getContainer()->get('database_connection');
        $sql = "SELECT html FROM tl_content as tc 
        LEFT JOIN tl_article ta on tc.pid = ta.id
        LEFT JOIN tl_page tp on ta.pid = tp.id
        WHERE tp.id = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->execute();
        $htmlElements = $stmt->fetchAll(FetchMode::COLUMN);
        $id = $layout->__get('id');
        $sql = "SELECT html FROM tl_module as tm 
                LEFT JOIN tl_layout tlayout on tlayout.id = tm.pid
                WHERE tlayout.id = ?"
        ;
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->execute();
        $htmlElementsLayout = $stmt->fetchAll(FetchMode::COLUMN);
        $htmlElements = array_merge($htmlElementsLayout,$htmlElements);
        if (empty($htmlElements))
            return $parameters;

        if (!empty($allModuleIds)) {
            $repoModule = new ModuleRepository($conn);
            $htmlElementsModule= $repoModule->findByIds($allModuleIds);
            $htmlElements = array_merge($htmlElementsModule,$htmlElements);
            $modIds = [];
            foreach ($htmlElements as $htmlElement) {
                $modId = self::getModuleIdFromHtmlElement($htmlElement);
                if (!empty($modId))
                    $modIds = array_merge($modId,$modIds);
            }
        }

        if (empty($modIds))
            return $parameters;

        $return = self::findCookieModuleByPid($modIds);
        if (empty($return))
            return $parameters;

        $parameters['moduleIds'] = $return['pid'];
        $parameters['tlCookieIds'] = $return['id'];

        return $parameters;

    }

    public static function getModuleIdFromHtmlElement($htmlElement)
    {
        $modId = [];
        $stringPositionEnd = 0;
        $saveEnd = 0;
        while($stringPositionEnd < strlen($htmlElement)) {
            $stringPositionStart = strpos($htmlElement,'{{insert_module::',$stringPositionEnd);
            if ($stringPositionStart !== false) {
                $stringPositionEnd = strpos($htmlElement,'}}',$stringPositionStart);
                if ($saveEnd == $stringPositionEnd) {
                    $stringPositionEnd = strlen($htmlElement);
                    continue;
                }
                $moduleTags
                    = substr(
                    $htmlElement,
                    $stringPositionStart,
                    $stringPositionEnd-$stringPositionStart)
                ;
                $modId[] = str_replace('{{insert_module::','',$moduleTags);
                $saveEnd = $stringPositionEnd;
            } else {
                $stringPositionEnd = strlen($htmlElement);
            }
        }
        return $modId;
    }

    /**
     * @param $modIds
     * @return mixed[]
     * @throws DBALException
     */
    public static function findCookieModuleByPid($modIds)
    {
        if (empty($modIds))
            return null;

        $conn = System::getContainer()->get('database_connection');
        $sql = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid IN (".implode(",",$modIds).")";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param LayoutModel|PageModel $layoutOrPage
     * @param                        $removeModules
     * @param array $moduleIds
     *
     * @return array
     * @throws DBALException
     */
    public static function checkModules($layoutOrPage, $removeModules, array $moduleIds) {

        $layoutModules = StringUtil::deserialize($layoutOrPage->__get('modules'));
        $tlCookieIds = [];
        $allModuleIds = [];
        $conn = System::getContainer()->get('database_connection');
        if (!empty($layoutModules)) {
            foreach ($layoutModules as $key => $layoutModule) {
                if (!empty($layoutModule['enable'])) {

                    $sql = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid = ?";
                    /** @var Statement $stmt */
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(1, $layoutModule['mod']);
                    $stmt->execute();
                    $bars = $stmt->fetchAll();

                    if (!empty($bars)) {
                        foreach ($bars as $bar) {
                            if ($removeModules) {
                                unset($layoutModules[$key]);
                            }
                            elseif(!in_array($bar['pid'],$moduleIds)) {
                                $moduleIds[] = $bar['pid'];
                            }
                            $tlCookieIds[] = $bar['id'];
                        }
                    }

                    $sql = "SELECT id,pid FROM tl_ncoi_cookie_revoke WHERE pid = ?";
                    /** @var Statement $stmt */
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(1, $layoutModule['mod']);
                    $stmt->execute();
                    $revokes = $stmt->fetchAll();
                    if (!empty($revokes)) {
                        foreach ($revokes as $revoke) {

                            if ($removeModules)
                                unset($layoutModules[$key]);
                            elseif(!in_array($revoke,$moduleIds))
                                $moduleIds[] = $revoke['pid'];
                        }
                    }
                    $allModuleIds[] = $layoutModule['mod'];
                }
            }
            $layoutOrPage->__set('modules', serialize($layoutModules));
        }
        if (empty($moduleIds)) {
            $pageId = $layoutOrPage->__get('id');
            $sql = "SELECT id,pid FROM tl_ncoi_cookie WHERE pid IN (SELECT module FROM tl_content AS content LEFT JOIN tl_article AS article ON article.id = content.pid LEFT JOIN tl_page AS page ON page.id = article.pid WHERE page.id = ? AND content.module <> 0 OR page.pid = ? AND content.module <> 0)";
            /** @var Statement $stmt */
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $pageId);
            $stmt->bindValue(2, $pageId);
            $stmt->execute();
            $bars = $stmt->fetchAll();
            if (!empty($bars)) {
                foreach ($bars as $bar) {
                    $tlCookieIds[] = $bar['id'];
                    $moduleIds[] = $bar['pid'];
                }
            }
        }

        return [
            'moduleIds' => $moduleIds,
            'tlCookieIds' => $tlCookieIds,
            'allModuleIds' => $allModuleIds
        ];
    }

    public static function setNewGroups($fieldPalette)
    {
        $save = false;
        switch ($fieldPalette->cookieToolsSelect) {
            case '-':
                $fieldPalette->cookieToolGroup = 1;
                $save = true;
                break;
            case 'googleAnalytics':
            case 'facebookPixel':
            case 'matomo':
                $fieldPalette->cookieToolGroup = 2;
                $save = true;
                break;
            case 'youtube':
            case 'vimeo':
            case 'googleMaps':
            case 'iframe':
                $fieldPalette->cookieToolGroup = 3;
                $save = true;
                break;
        };
        if ($save)
            $fieldPalette->save();
    }

    /**
     * @param PageModel $pageModel
     * @return bool
     * @throws Exception
     */
    public static function shouldRemoveModules(PageModel $pageModel)
    {
        $removeModules = false;
        $rootPage = ($pageModel->__get('type') == 'root') ? $pageModel : null;
        if (empty($rootPage)) {
            $rootPage = $pageModel->__get('rootId');
            $rootPage = PageModel::findByIdOrAlias($rootPage);
        }

        if ($rootPage->__get('bar_disabled')) {
            $removeModules = true;

        } else {
            $licenseKey = (!empty($rootPage->__get('ncoi_license_key'))) ? $rootPage->__get(
                'ncoi_license_key'
            ) : Config::get('ncoi_license_key');

            $licenseExpiryDate = (!empty($rootPage->__get('ncoi_license_expiry_date'))) ? $rootPage->__get(
                'ncoi_license_expiry_date'
            ) : Config::get('ncoi_license_expiry_date');

            if (!empty($licenseKey) && !empty($licenseExpiryDate) && !self::checkLicense(
                    $licenseKey,
                    $licenseExpiryDate,
                    $_SERVER['HTTP_HOST']
                ) && self::checkHash($licenseKey, $licenseExpiryDate, $_SERVER['HTTP_HOST'])) {

                $licenseAPIResponse = LicenseController::callAPI($_SERVER['HTTP_HOST']);
                if ($licenseAPIResponse->getSuccess()) {
                    $licenseExpiryDate = $licenseAPIResponse->getDateOfExpiry();
                    $licenseKey = $licenseAPIResponse->getLicenseKey();
                    LicenseController::setLicense($licenseExpiryDate, $licenseKey, $rootPage);
                }
            }

            if (!self::checkLicense(
                    $licenseKey,
                    $licenseExpiryDate,
                    $_SERVER['HTTP_HOST']
                ) && empty(self::checkLicenseRemainingTrialPeriod())) {

                $GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError']
                    = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInNoLicense.js|static';
                $removeModules = true;
            }
        }

        return $removeModules;
    }
}
