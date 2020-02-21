<?php

namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Config;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use DateTime;
use Exception;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use ModuleModel;

class PageLayoutListener {

	/**
	 * @param PageModel   $pageModel
	 * @param LayoutModel $layout
	 *
	 * @throws Exception
	 */
	public function onGetPageLayoutListener(PageModel $pageModel, LayoutModel $layout) {
		$removeModules = false;
		if (!self::checkLicense($pageModel))
			$removeModules = true;

		$moduleIds = [];
		$moduleIds[] = $this->checkModules($layout, $removeModules, $moduleIds);
		$moduleIds[] = $this->checkModules($pageModel, $removeModules, $moduleIds);
		$moduleIds = array_unique($moduleIds);

		//module exist
		if (empty($moduleIds)) {
			$GLOBALS['TL_JAVASCRIPT']['netzhirschError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInError.js|static';

			return;
		}
		elseif (count($moduleIds) > 1) {
			$GLOBALS['TL_JAVASCRIPT']['netzhirschError'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInErrorMore.js|static';

			return;
		}

		//module in this layout

		$modulBar = ModuleModel::findById($moduleIds[0]);

		$netzhirschOptInCookie = $_COOKIE['_netzhirsch_cookie_opt_in'];

		/** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this */
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

		if ($netzhirschOptInCookie->cookieVersion == $modulBar->__get('cookieVersion'))
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
				if ($cookieToolGroup != 'Essenziell') {
					setrawcookie($cookieToolsTechnicalName, 1, time() - 360000, '/', $_SERVER['HTTP_HOST']);
				}
			}
		}
	}

	/**
	 * @param PageModel $pageModel
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public static function checkLicense(PageModel $pageModel) {

		$licenseKey = (!empty($pageModel->__get('ncoi_license_key'))) ? $pageModel->__get('ncoi_license_key') : Config::get('ncoi_license_key');

		$domain = $_SERVER['HTTP_HOST'];
		$hashes[] = hash('sha256',$domain .'netzhirsch');

		//all possible subdomains
		$domainLevels = explode(".", $domain);

		foreach ($domainLevels as $key => $domainLevel) {
			if (count($domainLevels) < 2) break;
			unset($domainLevels[$key]);
			$domain = implode(".", $domainLevels);
			$hashes[] = hash('sha256',$domain .'netzhirsch');
		}

		if (in_array($licenseKey, $hashes)) {
			return true;
		} else {
			if (!empty(self::checkLicenseRemainingTrialPeriod())) {
				return true;
			}
			return false;
		}
	}

	public static function checkLicenseRemainingTrialPeriod(){
		$remainingTrialPeriod = null;

		$path = dirname(__DIR__);
		$filename = $path.DIRECTORY_SEPARATOR.'NetzhirschCookieOptInBundle.php';
		if (file_exists($filename)) {
			$fileTime = strtotime('+1 month',fileatime($filename));
			if ($fileTime > time()) {
				$remainingTrialPeriod = new DateTime();
				$remainingTrialPeriod->setTimestamp($fileTime-time());
			}
		}
		return $remainingTrialPeriod;
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