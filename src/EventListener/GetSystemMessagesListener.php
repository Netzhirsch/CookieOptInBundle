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
	 */
	public static function getMessage($licenseKey,$licenseExpiryDate,$domain = null) {

		$kontaktString = "Bitte kontaktieren Sie Netzhirsch unter <a href=\"tel:045138943740\">0451 / 38 94 37 40</a> oder <a href=\"mailto:netzhirsch@netzhirsch.de\">netzhirsch@netzhirsch.de</a>, um einen Lizenzschlüssel zu erwerben.";

		if (!empty($domain))
			$domainString = "<p>Für Ihre Domain: ".$domain.'</p>';

		$isLicense = false;

		if (!empty($licenseExpiryDate))
			$isLicense = PageLayoutListener::checkLicense($licenseKey,$licenseExpiryDate,$domain);

		if (empty($licenseKey)) {
			$dateInterval = PageLayoutListener::checkLicenseRemainingTrialPeriod();
			if (empty($dateInterval)) {
				return '<p class="tl_error">Probemonat für das Netzhirsch Cookie Opt In Bundle abgelaufen.<br><b>' .$domainString.
					   $kontaktString .
					   '</b></p>';
			} else {
				return '<p class="tl_info">Noch ' .
					   $dateInterval->d .
					   ' Tage vom Probemonat für das Netzhirsch Cookie Opt In Bundle.<br><b>' .$domainString.
					   $kontaktString .
					   '</b></p>';
			}
		} elseif ($isLicense) {

			$timeRemaining = PageLayoutListener::getLicenseRemainingExpiryDays($licenseExpiryDate);

			$expireIn = '';

			if ($timeRemaining->m < 2) {

				if( ($timeRemaining->d > 0)  )
					$expireIn .= ngettext(' einem Tag', ' '.$timeRemaining->d.' Tagen', $timeRemaining->d);

				if($timeRemaining->y == 0 && $timeRemaining->m > 0 && $timeRemaining->d > 0)
					$expireIn .= ' und';

				if($timeRemaining->m > 0)
					$expireIn .= ngettext(' einem Monat', ' '.$timeRemaining->m.' Monaten', $timeRemaining->m);

				if($timeRemaining->y > 0 && $timeRemaining->m > 0 && $timeRemaining->d <= 0)
					$expireIn .= ' und';

				if($timeRemaining->y > 0)
					$expireIn .= ngettext(' einem Jahr', ' '.$timeRemaining->y.' Jahre', $timeRemaining->y);
			}

			if (!empty($expireIn)){
				/** @noinspection HtmlUnknownTarget */
				$button = '<p><a href="/contao/license"><button>erneuern</button></a></p>';
				return '<p class="tl_info">Ihre Lizenz für das Netzhirsch Cookie Opt In Bundle erlischt in'.$expireIn.'.<br><b>'.$domainString.$button.
					   '</b></p>';
			}


		} elseif (!$isLicense) {
			return '<p class="tl_error">Keine gültige Lizenz für das Netzhirsch Cookie Opt In Bundle.<br><b>' .$domainString.
				   $kontaktString .
				   '</b></p>';
		}

		return '';
	}
}