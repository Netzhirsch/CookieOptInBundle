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

		$kontaktString = "Bitte kontaktieren Sie Netzhirsch unter <a href=\"tel:045138943740\">0451 / 38 94 37 40</a> oder <a href=\"mailto:netzhirsch@netzhirsch.de\">netzhirsch@netzhirsch.de</a>, um einen Lizenzschlüssel zu erwerben.";

		if (empty($domain)){
			$domain = $_SERVER['HTTP_HOST'];
		}
		$domainString = "Für Ihre Domain: ".$domain.'<br/>';

		if (empty($licenseKey) || empty($licenseExpiryDate)) {
			$dateInterval = PageLayoutListener::checkLicenseRemainingTrialPeriod();
			if (empty($dateInterval)) {
				return '<p class="tl_error">Der Probemonat für das Netzhirsch Cookie Opt In Bundle ist abgelaufen.<br><b>' .$domainString.
					   $kontaktString .
					   '</b></p>';
			} else {
				return '<p class="tl_info">Noch ' .
					   $dateInterval->d .
					   ' Tage vom Probemonat für das Netzhirsch Cookie Opt In Bundle übrig.<br><b>' .$domainString.
					   $kontaktString .
					   '</b></p>';
			}
		} elseif (PageLayoutListener::checkLicense($licenseKey,$licenseExpiryDate,$domain)) {
			$licenseExpiryDate =  date_create_from_format('Y-m-d', $licenseExpiryDate);
			$timeRemaining = PageLayoutListener::getLicenseRemainingExpiryDays($licenseExpiryDate);

			$expireIn = '';

			if ($timeRemaining->m < 2) {

				if(($timeRemaining->d == 0))
					$expireIn = ' weniger als 24 Stunden';

				if($timeRemaining->y > 0)
					$expireIn .= ngettext(' einem Jahr', ' '.$timeRemaining->y.' Jahre', $timeRemaining->y);

				if($timeRemaining->y > 0 && ($timeRemaining->m > 0 || $timeRemaining->d > 0))
					$expireIn .= ' und';

				if($timeRemaining->m > 0)
					$expireIn .= ngettext(' einem Monat', ' '.$timeRemaining->m.' Monaten', $timeRemaining->m);

				if($timeRemaining->y == 0 && $timeRemaining->m > 0 && $timeRemaining->d > 0)
					$expireIn .= ' und';

				if(($timeRemaining->d > 0) )
					$expireIn .= ngettext(' einem Tag', ' '.$timeRemaining->d.' Tagen', $timeRemaining->d);
			}

			if (!empty($expireIn)){
				/** @noinspection HtmlUnknownTarget */
				$button = '<p><a href="/contao/license"><button>erneuern</button></a></p>';
				return '<p class="tl_info">Ihre Lizenz für das Netzhirsch Cookie Opt In Bundle erlischt in'.$expireIn.'.<br><b>'.$domainString.$button.
					   '</b></p>';
			}


		} else {
			return '<p class="tl_error">Keine gültige Lizenz für das Netzhirsch Cookie Opt In Bundle.<br><b>' .$domainString.
				   $kontaktString .
				   '</b></p>';
		}

		return '';
	}
}