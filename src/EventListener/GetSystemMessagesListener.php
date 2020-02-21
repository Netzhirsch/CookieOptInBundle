<?php


namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\PageModel;
use Exception;

class GetSystemMessagesListener
{
	/**
	 * @return string
	 * @throws Exception
	 */
	public function onGetSystemMessages()
	{
		$rootPoints = PageModel::findByType('root');
		$license = [];
		foreach ($rootPoints as $rootPoint) {
			$license[] = PageLayoutListener::checkLicense($rootPoint);
		}

		$kontaktString = "Bitte kontaktieren Sie Netzhirsch unter <a href=\"tel:045138943740\">0451 / 38 94 37 40</a> oder <a href=\"mailto:netzhirsch@netzhirsch.de\">netzhirsch@netzhirsch.de</a>, um einen Lizenzschlüssel zu erwerben.";

		if (in_array(false, $license)) {
			return '<p class="tl_error">Keine gültige Lizenz oder Probemonat abgelaufen für das Netzhirsch Cookie Opt In Bundle.<br><b>' . $kontaktString . '</b></p>';
		}

		$dateInterval = PageLayoutListener::checkLicenseRemainingTrialPeriod();
		if (!empty($dateInterval)) {
			return '<p class="tl_info">Noch '.$dateInterval->d.' Tage vom Probemonat für das Netzhirsch Cookie Opt In Bundle.<br><b>' . $kontaktString . '</b></p>';
		}
		
		return '';
	}

}