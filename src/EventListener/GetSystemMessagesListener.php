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

		if (in_array(false, $license)) {
			return '<p class="tl_error">Keine gültige Lizenz oder Probemonat abgelaufen für das Netzhirsch Cookie Opt In Bundle</p>';
		}

		$remainingTrialPeriod = PageLayoutListener::checkLicenseRemainingTrialPeriod();
		if (!empty($remainingTrialPeriod)) {
			return '<p class="tl_info">Noch '.$remainingTrialPeriod->format('d').' Tage vom Probemonat für das Netzhirsch Cookie Opt In Bundle</p>';
		}
		
		return '';
	}

}