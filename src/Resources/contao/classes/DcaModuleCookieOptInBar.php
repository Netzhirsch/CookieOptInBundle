<?php

namespace Netzhirsch\CookieOptInBundle\Classes;

use Contao\Backend;

class DcaModuleCookieOptInBar extends Backend
{
	public function getCookieOptInBarTemplate()
	{
		return $this->getTemplate('mod_cookie_opt_in_bar');
	}
}