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
		foreach ($rootPoints as $rootPoint) {
			$licenseKey = (!empty($rootPoint->__get('ncoi_license_key'))) ? $rootPoint->__get('ncoi_license_key') : Config::get('ncoi_license_key');
		}

		$kontaktString = "Bitte kontaktieren Sie Netzhirsch unter <a href=\"tel:045138943740\">0451 / 38 94 37 40</a> oder <a href=\"mailto:netzhirsch@netzhirsch.de\">netzhirsch@netzhirsch.de</a>, um einen Lizenzschlüssel zu erwerben.";

		if (empty($licenseKey)) {
			$dateInterval = PageLayoutListener::checkLicenseRemainingTrialPeriod();
			if (empty($dateInterval)) {
				return '<p class="tl_error">Keine gültige Lizenz oder Probemonat abgelaufen für das Netzhirsch Cookie Opt In Bundle.<br><b>' .
					   $kontaktString .
					   '</b></p>';
			} else {
				return '<p class="tl_info">Noch ' .
					   $dateInterval->d .
					   ' Tage vom Probemonat für das Netzhirsch Cookie Opt In Bundle.<br><b>' .
					   $kontaktString .
					   '</b></p>';
			}
		} elseif (!PageLayoutListener::checkLicense($licenseKey)) {
			return '<p class="tl_error">Keine gültige Lizenz für das Netzhirsch Cookie Opt In Bundle.<br><b>' .
				   $kontaktString .
				   '</b></p>';

		}

		return '';
	}
}