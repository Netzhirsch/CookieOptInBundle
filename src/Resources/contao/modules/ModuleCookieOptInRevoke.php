<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\System;
use Doctrine\DBAL\Statement;
use Netzhirsch\CookieOptInBundle\Repository\Repository;

class ModuleCookieOptInRevoke extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cookie_opt_in_revoke';

    /**
     * @return string
     */
	public function generate() {
		
		if (TL_MODE == 'BE') {
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### Cookie Bar Revoke###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			// Code für Versionen ab 3.0.0
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
			return $objTemplate->parse();
		}

		return parent::generate();
	}

    /**
     */
	public function compile(){

		$this->strTemplate = 'mod_cookie_opt_in_revoke';
		$this->Template = new FrontendTemplate($this->strTemplate);

		$data = $this->Template->getData();
        $data['revokeButton'] = '';

        //********* revokue button aus dem Module Spache Array ********************************************************/
        System::loadLanguageFile('tl_module');
        if (
            isset($GLOBALS['TL_LANG'])
            && isset($GLOBALS['TL_LANG']['tl_module'])
            && !empty($GLOBALS['TL_LANG']['tl_module']['revokeButtonDefault'])
        ) {
            $data['revokeButton'] = $GLOBALS['TL_LANG']['tl_module']['revokeButtonDefault'];
        }
        //********* revokue button aus der Datenbank ******************************************************************/
        $repo = new Repository($this->Database);
        $strQuery = "SELECT revokeButton FROM tl_ncoi_cookie_revoke WHERE pid = ?";
        /** @var Statement $stmt */
        $result = $repo->findRow($strQuery,[], [$this->__get('id')]);
        if (!empty($result))
			$data['revokeButton'] = $result['revokeButton'];

		$this->Template->setData($data);
	}
}