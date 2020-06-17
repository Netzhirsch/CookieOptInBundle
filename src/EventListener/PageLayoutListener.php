<?php

namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Config;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Statement;
use Exception;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use ModuleModel;
use Netzhirsch\CookieOptInBundle\Controller\CookieController;
use Netzhirsch\CookieOptInBundle\Controller\LicenseController;

class PageLayoutListener {

	/**
	 * @param PageModel   $pageModel
	 * @param LayoutModel $layout
	 *
	 * @throws Exception
	 */
	public function onGetPageLayoutListener(PageModel $pageModel, LayoutModel $layout) {

		$removeModules = false;
        $rootPage = ($pageModel->__get('type') == 'root') ? $pageModel : null;
        if (empty($rootPage)) {
            $rootPage = $pageModel->__get('rootId');
            $rootPage = PageModel::findByIdOrAlias($rootPage);
        }
		$licenseKey = (!empty($rootPage->__get('ncoi_license_key'))) ? $rootPage->__get('ncoi_license_key') : Config::get('ncoi_license_key');
		$licenseExpiryDate = (!empty($rootPage->__get('ncoi_license_expiry_date'))) ? $rootPage->__get('ncoi_license_expiry_date') : Config::get('ncoi_license_expiry_date');

		if (!empty($licenseKey) && !empty($licenseExpiryDate) && !self::checkLicense($licenseKey, $licenseExpiryDate, $_SERVER['HTTP_HOST']) && self::checkHash($licenseKey, $licenseExpiryDate, $_SERVER['HTTP_HOST'])) {
			$licenseAPIResponse = LicenseController::callAPI($_SERVER['HTTP_HOST']);
			if ($licenseAPIResponse->getSuccess()) {
				$licenseExpiryDate = $licenseAPIResponse->getDateOfExpiry();
				$licenseKey = $licenseAPIResponse->getLicenseKey();
				LicenseController::setLicense($licenseExpiryDate,$licenseKey,$rootPage);
			}
		}

		if (!self::checkLicense($licenseKey, $licenseExpiryDate, $_SERVER['HTTP_HOST']) && empty(self::checkLicenseRemainingTrialPeriod())) {
			$GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInNoLicense.js|static';
			$removeModules = true;
		}

		$moduleIds = [];
		$moduleIds = self::checkModules($layout, $removeModules, $moduleIds);
		$moduleIds = self::checkModules($pageModel, $removeModules, $moduleIds);

		if ($removeModules) {
			return;
		}

		//for customer info
		if (!$removeModules) {
			if (empty($moduleIds)) {
				$GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInError.js|static';

				return;
			}
			elseif (count($moduleIds) > 2) {
				$GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInErrorMore.js|static';

				return;
			}
		}

		//module in this layout

        $modId = $moduleIds[0];
		$modulBar = ModuleModel::findById($modId);
        $conn = System::getContainer()->get('database_connection');
        $optInTechnicalName = CookieController::getOptInTechnicalCookieName($conn,$modId);
        $netzhirschOptInCookie = $_COOKIE[$optInTechnicalName];

        /********* update groups for a version < 1.3.0 ************************************************************/
        $sql = "SELECT cookieGroups,cookieVersion FROM tl_ncoi_cookie WHERE pid = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->execute();
        $result = $stmt->fetch();
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

		/** @noinspection PhpComposerExtensionStubsInspection */
		$netzhirschOptInCookie = json_decode($netzhirschOptInCookie);

        $fieldPalettes = FieldPaletteModel::findByPid($modulBar->id);
		$cookieTools = [];
		foreach ($fieldPalettes as $fieldPalette) {
			if ($fieldPalettes->pfield == 'cookieTools') {
				$cookieTools[] = $fieldPalette;
			}
            if ($fieldPalettes->cookieToolsTechnicalName = '_netzhirsch_cookie_opt_in') {
                $fieldPalettes->__set('cookieToolsSelect','optInCookie');
            }
            self::setNewGroups($fieldPalette);
		}

		if (self::doNotTrackBrowserSetting($modulBar, $modId))
			return;

		if (empty($netzhirschOptInCookie)) {
			self::deleteCookie();

			return;
		}

		if (!empty($modulBar) && $netzhirschOptInCookie->cookieVersion == $result['cookieVersion'])
			return;

		self::deleteCookie();
	}

	public static function doNotTrackBrowserSetting($modulBar = null, $modId = null) {
		$doNotTrack = false;

		if (empty($modul)) {
			//module in this layout
			$module = ModuleModel::findMultipleByIds($modId);
			if (!empty($module)) {
				foreach ($module as $modul) {
					if ($modul->type == 'cookieOptInBar') {
						$modulBar = $modul;
					}
				}
			}
		}

		if (
				array_key_exists('HTTP_DNT', $_SERVER) && (1 === (int) $_SERVER['HTTP_DNT']) && $modulBar->respectToNotTrack

		) {
			$doNotTrack = true;
			self::deleteCookie();
		}

		return $doNotTrack;
	}

    /**
     * @param array|null $cookieNotToDelete Cookies that should not be deleted
     */
	public static function deleteCookie(Array $cookieNotToDelete = null) {
        ob_start();
        $cookiesSet = $_COOKIE;
        if (!empty($cookieNotToDelete)) {
            foreach ($cookiesSet as $cookieSetTechnicalName => $value) {
                foreach ($cookieNotToDelete as $cookie) {
                    unset($cookiesSet[$cookie['cookieToolsTechnicalName']]);
                }
            }
        }
        $domain = explode('www',$_SERVER['HTTP_HOST']);
        if (is_array($domain) && count($domain) >= 2) {
            $domain = $domain[1];
        } else {
            $domain = '';
        }
        foreach ($cookiesSet as $cookieSetTechnicalName => $cookieSet) {
            if (
                $cookieSetTechnicalName == 'XDEBUG_SESSION'
                || $cookieSetTechnicalName == 'BE_USER_AUTH'
            )
                continue;
            setrawcookie($cookieSetTechnicalName, '', time() - 36000000, '/');
            setrawcookie($cookieSetTechnicalName, '', time() - 36000000, '/', $_SERVER['HTTP_HOST']);
            setrawcookie($cookieSetTechnicalName, '', time() - 36000000, '/', '.' . $_SERVER['HTTP_HOST']);
            setrawcookie(
                $cookieSetTechnicalName
                , ''
                , time() - 36000000
                , '/'
                , $domain
            );
        }
        ob_end_flush();
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
	 * @param LayoutModel|PageModel $layoutOrPage
	 * @param             			$removeModules
	 * @param array 				$moduleIds
	 *
	 * @return array
	 */
	public static function checkModules($layoutOrPage, $removeModules, array $moduleIds) {
		
		$layoutModules = StringUtil::deserialize($layoutOrPage->__get('modules'));
		if (!empty($layoutModules)) {
			foreach ($layoutModules as $key => $layoutModule) {
				if (!empty($layoutModule['enable'])) {

                    $conn = System::getContainer()->get('database_connection');
				    $sql = "SELECT pid FROM tl_ncoi_cookie";
                    /** @var Statement $stmt */
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(1, $layoutModule['mod']);
                    $stmt->execute();
                    $bar = $stmt->fetchColumn();

                    if (!empty($bar) && $removeModules)
                        unset($layoutModules[$key]);
                    elseif(!in_array($bar,$moduleIds))
                        $moduleIds[] = $bar;

                    $conn = System::getContainer()->get('database_connection');
                    $sql = "SELECT pid FROM tl_ncoi_cookie_revoke";
                    /** @var Statement $stmt */
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(1, $layoutModule['mod']);
                    $stmt->execute();
                    $revoke = $stmt->fetchColumn();

                    if (!empty($revoke) && $removeModules)
                        unset($layoutModules[$key]);
                    elseif(!in_array($revoke,$moduleIds))
                        $moduleIds[] = $revoke;
				}
			}
			$layoutOrPage->__set('modules', serialize($layoutModules));
		}

		return $moduleIds;
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
}
