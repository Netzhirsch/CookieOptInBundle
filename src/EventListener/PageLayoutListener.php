<?php

namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Config;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use DateInterval;
use DateTime;
use Exception;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use ModuleModel;
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

		$licenseKey = (!empty($pageModel->__get('ncoi_license_key'))) ? $pageModel->__get('ncoi_license_key') : Config::get('ncoi_license_key');
		$licenseExpiryDate = (!empty($pageModel->__get('ncoi_license_expiry_date'))) ? $pageModel->__get('ncoi_license_expiry_date') : Config::get('ncoi_license_expiry_date');

		if (!self::checkLicense($licenseKey,$licenseExpiryDate,$_SERVER['HTTP_HOST']) && empty(self::checkLicenseRemainingTrialPeriod())) {

			$licenseExpiryDate = LicenseController::callAPI($_SERVER['HTTP_HOST']);
			if (empty($licenseExpiryDate)) {

				$GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInNoLicense.js|static';
				$removeModules = true;
			} else {

				LicenseController::setLicense($licenseExpiryDate,$_SERVER['HTTP_HOST']);
			}
		}

		$moduleIds = [];
		$moduleIds = $this->checkModules($layout, $removeModules, $moduleIds);
		$moduleIds = $this->checkModules($pageModel, $removeModules, $moduleIds);
		$moduleIds = array_unique($moduleIds);

		if ($removeModules) {
			return;
		}

		//for customer info
		if (!$removeModules) {
			if (empty($moduleIds)) {
				$GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInError.js|static';

				return;
			}
			elseif (count($moduleIds) > 1) {
				$GLOBALS['TL_JAVASCRIPT']['netzhirschCookieOptInError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInErrorMore.js|static';

				return;
			}
		}

		//module in this layout

		$modulBar = ModuleModel::findById($moduleIds[0]);

		$netzhirschOptInCookie = $_COOKIE['_netzhirsch_cookie_opt_in'];

		$netzhirschOptInCookie = json_decode($netzhirschOptInCookie);

		$paletteModule = FieldPaletteModel::findByPid($modulBar->id);
		$cookieTools = [];
		foreach ($paletteModule as $palettModul) {
			if ($palettModul->pfield == 'cookieTools') {
				$cookieTools[] = $palettModul;
			}
		}

		if (self::doNotTrackBrowserSetting($modulBar, $cookieTools))
			return;

		if (empty($netzhirschOptInCookie) || !$netzhirschOptInCookie->allowed) {
			self::deleteCookie($cookieTools);

			return;
		}

		if (!empty($modulBar) && $netzhirschOptInCookie->cookieVersion == $modulBar->__get('cookieVersion'))
			return;

		self::deleteCookie($cookieTools);
	}

	public static function doNotTrackBrowserSetting($cookieTools, $modulBar = null, $modId = null) {
		$doNotTrack = false;

		if (empty($modul)) {
			//module in this layout
			$module = ModuleModel::findMultipleByIds($modId);
			foreach ($module as $modul) {
				if ($modul->type == 'cookieOptInBar') {
					$modulBar = $modul;
				}
			}
		}

		if (
				array_key_exists('HTTP_DNT', $_SERVER) && (1 === (int) $_SERVER['HTTP_DNT']) && $modulBar->respectToNotTrack

		) {
			$doNotTrack = true;
			self::deleteCookie($cookieTools);
		}

		return $doNotTrack;
	}

	public static function deleteCookie(Array $toolTypes) {
		foreach ($toolTypes as $toolTyp) {
			$cookieToolsTechnicalName = $toolTyp->cookieToolsTechnicalName;
			if (empty($cookieToolsTechnicalName))
				$cookieToolsTechnicalName = $toolTyp['cookieToolsTechnicalName'];
			$cookieToolsTechnicalNames = explode(',', $cookieToolsTechnicalName);

			foreach ($cookieToolsTechnicalNames as $cookieToolsTechnicalName) {
				$cookieToolGroup = $toolTyp->cookieToolGroup;
				if (empty($cookieToolGroup))
					$cookieToolGroup = $toolTyp['cookieToolGroup'];
				if ($cookieToolGroup != 'Essenziell' && $cookieToolGroup != 'essential') {
					setrawcookie($cookieToolsTechnicalName, 1, time() - 360000, '/', $_SERVER['HTTP_HOST']);
				}
			}
		}
	}

	/**
	 * @param string   $licenseKey
	 * @param string $licenseExpiryDate
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function checkLicense($licenseKey,$licenseExpiryDate,$domain) {

		/** @var DateInterval $licenseExpiryTimeDiff */
		$licenseExpiryTimeDiff = self::getLicenseRemainingExpiryDays($licenseExpiryDate);

		if ($licenseExpiryTimeDiff !== false) {
			if($licenseExpiryTimeDiff->invert == 0) {
				return false;
			}
		}

		// in Frontend Y-m-d in Backend d.m.Y
		if (strpos($licenseExpiryDate, '.') === false) {
			$licenseExpiryDate = date_create_from_format('Y-m-d', $licenseExpiryDate);
		} else {
			$licenseExpiryDate = date_create_from_format('d.m.Y', $licenseExpiryDate);
		}

		$hashes[] = LicenseController::getHash($domain, $licenseExpiryDate);

		//all possible subdomains
		$domainLevels = explode(".", $domain);

		foreach ($domainLevels as $key => $domainLevel) {
			if (count($domainLevels) < 2) break;
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
	 * @param string $licenseExpiryDate
	 *
	 * @return DateInterval|false
	 * @throws Exception
	 */
	public static function getLicenseRemainingExpiryDays($licenseExpiryDate) {
		$today = new DateTime('now');
		$licenseExpiryDate = date_create_from_format('d.m.Y', $licenseExpiryDate);
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
			$fileTime = strtotime('+1 month',fileatime($filename));
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
	private function checkModules($layoutOrPage, $removeModules, array $moduleIds) {
		$layoutModules = StringUtil::deserialize($layoutOrPage->__get('modules'));
		foreach ($layoutModules as $key => $layoutModule) {
			if (!empty($layoutModule['enable'])) {
				$mod = ModuleModel::findById($layoutModule['mod']);
				if ($mod->type == 'cookieOptInBar') {
					if ($removeModules)
						unset($layoutModules[$key]);
					else
						$moduleIds[] = $mod->id;
				} elseif ($mod->type == 'cookieOptInRevoke' && $removeModules) {
					unset($layoutModules[$key]);
				}
			}
		}
		$layoutOrPage->__set('modules', serialize($layoutModules));

		return $moduleIds;
	}
}