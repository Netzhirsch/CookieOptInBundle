<?php

namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Config;
use Contao\Database;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\ThemeModel;
use DateInterval;
use DateTime;
use Exception;
use Netzhirsch\CookieOptInBundle\Controller\CookieController;
use Netzhirsch\CookieOptInBundle\Controller\LicenseController;
use Netzhirsch\CookieOptInBundle\Repository\BarRepository;
use Netzhirsch\CookieOptInBundle\Repository\ModuleRepository;
use Netzhirsch\CookieOptInBundle\Repository\Repository;
use Netzhirsch\CookieOptInBundle\Repository\RevokeRepository;

class PageLayoutListener {

    /** @var Database $database */
    private $database;

    public function __construct()
    {
        $this->database = Database::getInstance();
    }

    /**
     * @param PageModel $pageModel
     * @param LayoutModel $layout
     *
     * @throws Exception
     */
    public function onGetPageLayoutListener(PageModel $pageModel, LayoutModel $layout) {

        $removeModules = $this->shouldRemoveModules($pageModel);
        $moduleIds = [];
        $return = self::checkModules($layout,$this->database, $removeModules, $moduleIds);
        $allModuleIdsInLayout = $return['allModuleIds'];
        $moduleIds = $return['moduleIds'];
        if (!empty($return['tlCookieIds']))
            $tlCookieIds[] = $return['tlCookieIds'];
        else
            $return = self::checkModules($pageModel,$this->database, $removeModules, $moduleIds);
        $moduleIds = $return['moduleIds'];
        $repo = new Repository($this->database);
        if (empty($moduleIds)) {
            // remove in done on onParseFrontendTemplate event listener
            if (!$removeModules) {
                $return = self::getModuleIdFromInsertTag($pageModel,$layout,$this->database,$allModuleIdsInLayout);
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
        $strQuerySelectCookieGroups = "SELECT cookieGroups,cookieVersion,respectDoNotTrack FROM tl_ncoi_cookie WHERE pid = ?";
        $result = $repo->findRow($strQuerySelectCookieGroups,[],[$modId]);
        if (empty($result))
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
            $strQueryUpdateCookieGroups = "UPDATE tl_ncoi_cookie SET cookieGroups = %s WHERE pid = ?";
            $repo->executeStatement($strQueryUpdateCookieGroups,[serialize($newValues)],[$modId]);
        }

        if (self::doNotTrackBrowserSetting($result['respectDoNotTrack'],$modId))
            CookieController::deleteCookies();
    }

    private function doNotTrackBrowserSetting($respectDoNotTrack,$moduleId) {
        $doNotTrack = false;
        if (empty($respectDoNotTrack)) {
            $conn = $this->database;
            $barRepository = new BarRepository($conn);
            $respectDoNotTrack = $barRepository->findByPid($moduleId)['respectDoNotTrack'];
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

        if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']))
            return true;

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
     * @param PageModel|string $page
     * @param LayoutModel $layout
     * @param Database $database
     * @param null $allModuleIds
     * @return array
     */
    public static function getModuleIdFromInsertTag($page,LayoutModel $layout,Database $database,$allModuleIds = null)
    {
        if (is_string($page))
            $page = PageModel::findById($page);

        $id = $page->__get('id');
        $repo = new Repository($database);
        $strQueryContent = "SELECT html FROM tl_content as tc
        LEFT JOIN tl_article ta on tc.pid = ta.id
        LEFT JOIN tl_page tp on ta.pid = tp.id
        WHERE tp.id = ?";
        $htmlElements = $repo->findAllAssoc($strQueryContent,[], [$id]);
        if (empty($htmlElements))
            $htmlElements = [];
        $id = $layout->__get('id');
        $strQueryModule = "SELECT html FROM tl_module as tm
                LEFT JOIN tl_layout tlayout on tlayout.id = tm.pid
                WHERE tlayout.id = ?"
        ;
        $htmlElementsLayout = $repo->findAllAssoc($strQueryModule,[], [$id]);
        if (empty($htmlElementsLayout))
            $htmlElementsLayout = [];
        $htmlElements = array_merge($htmlElementsLayout,$htmlElements);

        $parameters = [
            'moduleIds' => null,
            'tlCookieIds' => null
        ];
        if (empty($htmlElements))
            return $parameters;

        if (!empty($allModuleIds)) {
            $repoModule = new ModuleRepository($database);
            $htmlElementsModule = $repoModule->findByIds($allModuleIds);
            $htmlElements = array_merge($htmlElementsModule,$htmlElements);
        }

        $modIds = [];
        foreach ($htmlElements as $htmlElement) {
            $html = $htmlElement['html'];
            if (empty($html))
                continue;
            $modId = self::getModuleIdFromHtmlElement($html);
            if (!empty($modId) && is_array($modId))
                $modIds = array_merge($modId,$modIds);
        }


        if (empty($modIds))
            return $parameters;
        $barRepo = new BarRepository($database);

        $return = $barRepo->findByIds($modIds);
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
        if (is_array($htmlElement))
            return $modId;

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
                $id = str_replace('{{insert_module::','',$moduleTags);
                if (is_numeric($id))
                    $modId[] = $id;
                $saveEnd = $stringPositionEnd;
            } else {
                $stringPositionEnd = strlen($htmlElement);
            }
        }
        return $modId;
    }

    /**
     * @param LayoutModel|PageModel $layoutOrPage
     * @param                        $removeModules
     * @param array $moduleIds
     * @return array
     */
    public static function checkModules($layoutOrPage, Database $database,$removeModules, array $moduleIds) {
        if (is_string($layoutOrPage))
            $layoutOrPage = PageModel::findById($layoutOrPage);

        $layoutModules = StringUtil::deserialize($layoutOrPage->__get('modules'));
        $tlCookieIds = [];
        $allModuleIds = [];
        $barRepository = new BarRepository($database);

        if (!empty($layoutModules)) {
            $bars = $barRepository->findAll();
            $revokeRepository = new RevokeRepository($database);

            foreach ($layoutModules as $key => $layoutModule) {
                if (empty($layoutModule['enable']) || empty($layoutModule['mod']))
                        continue;
                if (!empty($bars)) {
                    foreach ($bars as $bar) {
                        if ($removeModules && $bar['pid'] == $layoutModule['mod']) {
                            unset($layoutModules[$key]);
                        }
                        elseif(!in_array($bar['pid'],$moduleIds)) {
                            $moduleIds[] = $bar['pid'];
                        }
                        $tlCookieIds[] = $bar['id'];
                    }
                }
                $revokes = $revokeRepository->findByPid($layoutModule['mod']);
                if (!empty($revokes)) {
                    foreach ($revokes as $revoke) {

                        if ($removeModules)
                            unset($layoutModules[$key]);
                        elseif(!in_array($revoke,$moduleIds))
                            $moduleIds[] = $revoke;
                    }
                }
                $allModuleIds[] = $layoutModule['mod'];
            }
            $layoutOrPage->__set('modules', serialize($layoutModules));
        }
        if (empty($moduleIds) || empty($allModuleIds)) {

            $pageId = $layoutOrPage->__get('id');
            $bars = $barRepository->findByLayoutOrPage($pageId);
            if (!empty($bars)) {
                foreach ($bars as $bar) {
                    $tlCookieIds[] = $bar['id'];
                    $moduleIds[] = $bar['pid'];
                    $allModuleIds[] = $bar['pid'];
                }
            } elseif(get_class($layoutOrPage) == PageModel::class) {

                global $objPage;
                // Get the page layout
                $objLayout = LayoutModel::findByPk($objPage->layout);

                /** @var ThemeModel $objTheme */
                $objTheme = $objLayout->getRelated('pid');

                // Set the layout template and template group
                $template = $objLayout->template ?: 'fe_page';
                $template .= '.html5';

                $dir = TL_ROOT
                    . DIRECTORY_SEPARATOR
                    . 'templates'
                ;
                $modId = null;

                $templateFile = self::getTemplateFile($dir,$template);

                if (file_exists($templateFile)) {
                    $content = file_get_contents($templateFile);
                    $modId = self::getModuleIdFromTemplate($content,$database);
                }
                if (!empty($modId)) {
                    $tlCookieIds[] = $modId;
                    $moduleIds[] = $modId;
                    $allModuleIds[] = $modId;
                }
            }
        }

        return [
            'moduleIds' => $moduleIds,
            'tlCookieIds' => $tlCookieIds,
            'allModuleIds' => $allModuleIds
        ];
    }

    private static function getTemplateFile($dir,$template){
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..')
                continue;
            if (is_dir($dir.DIRECTORY_SEPARATOR.$file)) {
                $subDir = $dir.DIRECTORY_SEPARATOR.$file;
                return self::getTemplateFile($subDir,$template);
            } else {
                if ($file == $template) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            }
        }
        return '';
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
        }
        if ($save)
            $fieldPalette->save();
    }

    public static function isDisabled(PageModel $pageModel)
    {
        $rootPage = ($pageModel->__get('type') == 'root') ? $pageModel : null;

        if (empty($rootPage)) {
            $rootId = $pageModel->__get('rootId');
            $rootPage = PageModel::findByIdOrAlias($rootId);
        }

        if ($rootPage->__get('bar_disabled'))
            return true;

        return false;
    }
    /**
     * @param PageModel $pageModel
     * @return bool
     * @throws Exception
     */
    public static function shouldRemoveModules(PageModel $pageModel)
    {

        if (self::isDisabled($pageModel))
            return true;

        $rootPage = ($pageModel->__get('type') == 'root') ? $pageModel : null;
        if (empty($rootPage)) {
            $rootPage = $pageModel->__get('rootId');
            $rootPage = PageModel::findByIdOrAlias($rootPage);
        }

        $licenseKey = (!empty($rootPage->__get('ncoi_license_key'))) ? $rootPage->__get(
            'ncoi_license_key'
        ) : Config::get('ncoi_license_key');

        $licenseExpiryDate = (!empty($rootPage->__get('ncoi_license_expiry_date'))) ? $rootPage->__get(
            'ncoi_license_expiry_date'
        ) : Config::get('ncoi_license_expiry_date');

        $domain = $_SERVER['SERVER_NAME'];
        if (!empty($licenseKey) && !empty($licenseExpiryDate) && !self::checkLicense(
                $licenseKey,
                $licenseExpiryDate,
                $domain
            ) && self::checkHash($licenseKey, $licenseExpiryDate, $domain)) {

            $licenseAPIResponse = LicenseController::callAPI($domain,true);
            if ($licenseAPIResponse->getSuccess()) {
                $licenseExpiryDate = $licenseAPIResponse->getDateOfExpiry();
                $licenseKey = $licenseAPIResponse->getLicenseKey();
                LicenseController::setLicense($licenseExpiryDate, $licenseKey, $rootPage);
            }
        }

        if (!self::checkLicense(
                $licenseKey,
                $licenseExpiryDate,
                $domain
            ) && empty(self::checkLicenseRemainingTrialPeriod())) {

            $GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError']
                = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInNoLicense.js|static';
            return true;
        }

        return false;
    }

    public static function getModuleIdFromTemplate($fileContent,Database $database)
    {
        if (!$fileContent)
            return '';

        $modId = self::findModIdInFileContentWithInLangTag($fileContent,$database);
        if (empty($modId))
            $modId = self::findModIdInFileContent($fileContent,$database);



        return $modId;
    }

    public static function findModIdInFileContentWithInLangTag($fileContent,Database $database) {
        $stringPositionEndLang = 0;
        $modId = null;
        $stringPositionStartLang = strpos($fileContent,'{{iflng::'.$GLOBALS['TL_LANGUAGE'],$stringPositionEndLang);
        while($stringPositionStartLang != false) {
            if (empty($stringPositionEndLang))
                $stringPositionEndLang = strpos($fileContent,'{{iflng}}',$stringPositionStartLang+strlen('{{iflng::'));
            if (empty($stringPositionEndLang))
                return null;

            $insertModule = substr(
                $fileContent,
                $stringPositionStartLang,
                $stringPositionEndLang-$stringPositionStartLang);

            $stringPositionStart = strpos($insertModule,'{{insert_module::');
            if ($stringPositionStart !== false) {
                $stringPositionEnd = strpos($insertModule,'}}',$stringPositionStart);
                $moduleTags
                    = substr(
                    $insertModule,
                    $stringPositionStart,
                    $stringPositionEnd-$stringPositionStart)
                ;
                $modId = str_replace('{{insert_module::','',$moduleTags);
                $barRepo = new BarRepository($database);
                if (!empty($modId) && is_numeric($modId)) {
                    $return = $barRepo->findByIds([$modId]);
                    if (empty($return)) {
                        $stringPositionEndLang = strpos($fileContent,'{{iflng}}',$stringPositionStartLang+strlen('{{iflng::'));
                        $modId = null;
                    } else {
                        break;
                    }
                }
            } else {
                $stringPositionEndLang = strpos($fileContent,'{{iflng}}',$stringPositionStartLang+strlen('{{iflng::'));
            }
            $stringPositionStartLang = strpos($fileContent,'{{iflng::'.$GLOBALS['TL_LANGUAGE'],$stringPositionEndLang);
        }
        return $modId;
    }

    public static function findModIdInFileContent($fileContent,Database $database) {
        $offset = 0;
        $modId = null;
        $stringPositionStartLWithoutIfLang = true;
        while($stringPositionStartLWithoutIfLang != false) {
            $stringPositionStartLWithoutIfLang = strpos($fileContent,'{{insert_module::',$offset);
            $stringPositionEndWithoutIfLang = strpos($fileContent,'}}',$stringPositionStartLWithoutIfLang);
            $offset = $stringPositionEndWithoutIfLang;
            $insertModule = substr(
                $fileContent,
                $stringPositionStartLWithoutIfLang,
                $stringPositionEndWithoutIfLang-$stringPositionStartLWithoutIfLang)
            ;
            $modId = str_replace('{{insert_module::','',$insertModule);
            $modId = str_replace('}}','',$modId);
            $modId = trim($modId);
            $barRepo = new BarRepository($database);
            if (!empty($modId) && is_numeric($modId)) {
                $return = $barRepo->findByIds([$modId]);
                if (empty($return))
                    $modId = null;
                else
                    break;
            }
        }

        return $modId;
    }
}
