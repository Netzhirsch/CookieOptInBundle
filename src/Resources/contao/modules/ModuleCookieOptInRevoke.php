<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\LayoutModel;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Statement;

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
	
	public function compile(){
		
		$this->strTemplate = 'mod_cookie_opt_in_revoke';

		$this->Template = new FrontendTemplate($this->strTemplate);

		$data = $this->Template->getData();
        $conn = System::getContainer()->get('database_connection');

        $sql = "SELECT revokeButton FROM tl_ncoi_cookie_revoke WHERE pid = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $this->__get('id'));
        $stmt->execute();
        $result = $stmt->fetch();

		$revokeButton = $result['revokeButton'];
		if (!empty($revokeButton))
			$data['revokeButton'] = $revokeButton;

		$layout = LayoutModel::findById($this->__get('pid'));
        $modules = $layout->modules;
        $modules = StringUtil::deserialize($modules);
        $sql = "SELECT pid FROM tl_ncoi_cookie";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $this->__get('id'));
        $stmt->execute();
        $results = $stmt->fetchAll();
        $moduleBarInLayout = false;
        foreach ($modules as $module) {
            foreach ($results as $result) {
                if ($result['pid'] == $module['mod']) {
                    $moduleBarInLayout = true;
                }
            }
        }
		if (!$moduleBarInLayout)
			$data['moduleMissing'] = 'bar modul not in layout';
		
        $data['currentPage'] = $_SERVER['REDIRECT_URL'];

		$this->Template->setData($data);
	}
}