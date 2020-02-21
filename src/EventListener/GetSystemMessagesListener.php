<?php


namespace Netzhirsch\CookieOptInBundle\EventListener;

use Contao\PageModel;

class GetSystemMessagesListener
{
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
		return '';
	}

}