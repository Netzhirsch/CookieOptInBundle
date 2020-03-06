<?php


namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\Config;
use Contao\PageModel;
use Exception;

class GetSystemMessagesListener
{
	/**
	 * @return string
	 * @throws Exception
	 */
	public function onGetSystemMessages() {
		$rootPoints = PageModel::findByType('root');
		$licenseExpiryDate = null;
		$licenseKey = null;
		$messages = '';
		$domain = null;
		foreach ($rootPoints as $rootPoint) {
			$licenseKey = $rootPoint->__get('ncoi_license_key');
			if (empty($licenseKey)) {
				$licenseKey = Config::get('ncoi_license_key');
				$licenseExpiryDate = Config::get('ncoi_license_expiry_date');
			} else {
				$licenseExpiryDate = $rootPoint->__get('ncoi_license_expiry_date');
			}
			$domain = $rootPoint->__get('dns');
			if (!empty($domain))
				$messages .= self::getMessage($licenseKey,$licenseExpiryDate,$domain);
		}
		if (empty($domain))
			$messages .= self::getMessage($licenseKey,$licenseExpiryDate);

		return $messages;
	}

	/**
	 * @param $licenseKey
	 * @param $licenseExpiryDate
	 *
	 * @param $domain
	 *
	 * @return string
	 * @throws Exception
	 * @noinspection PhpComposerExtensionStubsInspection ext-gettext require in bundle composer.json
	 */
	public static function getMessage($licenseKey,$licenseExpiryDate,$domain = null) {

		$kontaktString = $GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['contact'];

		if (empty($domain)){
			$domain = $_SERVER['HTTP_HOST'];
		}
		$domainString = $GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['domain'].$domain.'<br/>';

		if (empty($licenseKey) || empty($licenseExpiryDate)) {
			$dateInterval = PageLayoutListener::checkLicenseRemainingTrialPeriod();
			if (empty($dateInterval)) {
				return '<p class="tl_error">'.$GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['errorTrial'].'<br><b>' .$domainString.
					   $kontaktString .
					   '</b></p>';
			} else {
				return '<p class="tl_info">'.$GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['infoTrialStart'].' ' .
					   $dateInterval->d .
					$GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['infoTrialEnd'].'<br><b>' .$domainString.
					   $kontaktString .
					   '</b></p>';
			}
		} elseif (PageLayoutListener::checkLicense($licenseKey,$licenseExpiryDate,$domain)) {
			$licenseExpiryDate =  date_create_from_format('Y-m-d', $licenseExpiryDate);
			$timeRemaining = PageLayoutListener::getLicenseRemainingExpiryDays($licenseExpiryDate);

			$expireIn = '';

		$expireInLang = $GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['infoLicenseRemainingDate'];

			if ($timeRemaining->m < 2) {

				if(($timeRemaining->d == 0))
					$expireIn = $expireInLang['lessHours'];

				if($timeRemaining->y > 0)
					$expireIn .= ngettext($expireInLang['oneYear'], ' '.$timeRemaining->y.$expireInLang['years'], $timeRemaining->y);

				if($timeRemaining->y > 0 && ($timeRemaining->m > 0 || $timeRemaining->d > 0))
					$expireIn .= $expireInLang['and'];

				if($timeRemaining->m > 0)
					$expireIn .= ngettext($expireInLang['oneMonth'], ' '.$timeRemaining->m.$expireInLang['months'], $timeRemaining->m);

				if($timeRemaining->y == 0 && $timeRemaining->m > 0 && $timeRemaining->d > 0)
					$expireIn .= $expireInLang['and'];

				if(($timeRemaining->d > 0) )
					$expireIn .= ngettext($expireInLang['oneDay'], ' '.$timeRemaining->d.$expireInLang['days'], $timeRemaining->d);
			}

			if (!empty($expireIn)){
				$button = '<p><a href="/contao/license"><button>'.$expireInLang['button'].'</button></a></p>';
				return '<p class="tl_info">'.$expireInLang['message'].$expireIn.'.<br><b>'.$domainString.$button.
					   '</b></p>';
			}


		} else {
			return '<p class="tl_error">'.$GLOBALS['TL_LANG']['BE_MOD']['netzhirsch']['cookieOptIn']['messages']['errorLicense'].'<br><b>' .$domainString.
				   $kontaktString .
				   '</b></p>';
		}

		return '';
	}
}