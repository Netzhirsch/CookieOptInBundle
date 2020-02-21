<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\Backend;

class DcaModuleCookieOptInRevoke extends Backend
{
	public function getCookieOptInRevokeTemplate()
	{
		return $this->getTemplate('mod_cookie_opt_in_revoke');
	}
}