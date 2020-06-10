<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\ModuleModel;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;

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
		
		$cookieOptInBarModule = ModuleModel::findByType('cookieOptInBar');
		$moduleBarInLayout = null;
		foreach ($cookieOptInBarModule as $cookieOptInBarModul) {
			if ($cookieOptInBarModul->pid == $this->pid) {
				$moduleBarInLayout = $cookieOptInBarModul;
			}
		}
		$cookieTools = FieldPaletteModel::findByPid($moduleBarInLayout->id);
		$data['cookieTools'] = [];
		$data['otherScripts'] = [];
		foreach ($cookieTools as $cookieTool) {
			if ($cookieTool->pfield == 'cookieTools') {
				$data['cookieTools'][] = $cookieTool;
			}
			if ($cookieTool->pfield == 'otherScripts') {
				$data['otherScripts'][] = $cookieTool;
			}
		}
		
		if (PageLayoutListener::doNotTrackBrowserSetting($this->id))
			return null;
		
		$revokeButton = $this->arrData['revokeButton'];
		if (!empty($revokeButton))
			$data['revokeButton'] = $revokeButton;
		
		if (empty($moduleBarInLayout))
			$data['moduleMissing'] = 'bar modul not in layout';
		
		$data['animation'] = $cookieOptInBarModule->__get('animation');

        $data['currentPage'] = $_SERVER['REDIRECT_URL'];

		$this->Template->setData($data);
	}
}