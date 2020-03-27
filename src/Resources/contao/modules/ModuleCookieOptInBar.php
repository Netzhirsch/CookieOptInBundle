<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Less_Exception_Parser;
use Netzhirsch\CookieOptInBundle\Classes\Helper;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ModuleCookieOptInBar extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cookie_opt_in_bar';
	
	/**
	 * @return string
	 */
	public function generate() {
		
		if (TL_MODE == 'BE') {
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### Cookie Bar ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
//			 Code für Versionen ab 3.0.0
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	/**
	 * @throws Less_Exception_Parser
	 */
	public function compile(){
		
		$this->strTemplate = 'mod_cookie_opt_in_bar';
		$tlLang = $GLOBALS['TL_LANG']['FMD']['netzhirsch']['cookieOptIn']['module'];
		
		$this->Template = new FrontendTemplate($this->strTemplate);
		$data = $this->Template->getData();
		
		self::setCssJs($this->__get('defaultCss'),$this->__get('cssTemplateStyle'),$this->__get('maxWidth'));
		
		$data['cookieTools'] = FieldPaletteModel::findByPid($this->id);
		
		if (PageLayoutListener::doNotTrackBrowserSetting($data['cookieTools'],$this->id))
			return null;
		
		$netzhirschOptInCookie = self::getCookieData(System::getContainer());
		$data['cookieGroupsSelected'] = $netzhirschOptInCookie->groups;
		$data['cookieGroupsSelected'][] = $tlLang['cookieGroup']['essential'];
		$data['cookieGroups'][] = $tlLang['cookieGroup']['essential'];
		
		foreach ($data['cookieTools'] as $cookieTool) {
			foreach ($netzhirschOptInCookie->cookieIds as $cookieId) {
				if ($cookieId == $cookieTool->id) {
					if (!in_array($cookieTool->cookieToolGroup, $data['cookieGroupsSelected'])) {
						$data['cookieGroupsSelected'][] = $cookieTool->cookieToolGroup;
					}
				}
			}
			if (!in_array($cookieTool->cookieToolGroup, $data['cookieGroups']) && $cookieTool->cookieToolGroup != 'essential' && $cookieTool->cookieToolGroup != 'Essenziell') {
				$data['cookieGroups'][] = $cookieTool->cookieToolGroup;
			}
			
		}
		
		$data['cookiesSelected'] = '';
		$data['cookiesSelected'] = $netzhirschOptInCookie->cookieIds;
		$data['id'] = $this->id;
		$data['netzhirschCookieIsSet'] = false;
		if (!empty($netzhirschOptInCookie))
			$data['netzhirschCookieIsSet'] = true;
		
		$data['netzhirschCookieIsVersionNew'] = "0";
		if ($netzhirschOptInCookie->cookieVersion < $this->__get('cookieVersion'))
			$data['netzhirschCookieIsVersionNew'] = "1";
		
		if (!empty($this->headlineCookieOptInBar)) {
			$headlineData = StringUtil::deserialize($this->headlineCookieOptInBar);
			$data['headlineCookieOptInBar'] = "<".$headlineData['unit']." class=\"ncoi---headline\">".$headlineData['value']."</".$headlineData['unit'].">";
		}
		
		$questionHint = $this->arrData['questionHint'];
		if (!empty($questionHint))
			$data['questionHint'] = $questionHint;
		
		$impress = PageModel::findById($this->__get('impress'));
		if (!empty($impress)) {
			$impress = $impress->getFrontendUrl();
			$impress = '<a class="ncoi---link" href="'.$impress.'" title ="'.$tlLang['impress'].'"> '.$tlLang['impress'].' </a>';
			$data['impress'] = $impress;
		}
		
		$privacyPolicy = PageModel::findById($this->__get('privacyPolicy'));
		if (!empty($privacyPolicy)) {
			$privacyPolicy = $privacyPolicy->getFrontendUrl();
			$privacyPolicy = '<a class="ncoi---link" href="'.$privacyPolicy.'" title ="'.$tlLang['privacyPolicy'].'"> '.$tlLang['privacyPolicy'].' </a>';
			$data['privacyPolicy'] = $privacyPolicy;
		}
		
		$infoTitle = $this->arrData['infoTitle'];
		if (!empty($infoTitle)) {
			$infoTitle = StringUtil::deserialize($infoTitle);
			$data['infoTitle'] = "<".$infoTitle['unit'].">".$infoTitle['value']."</".$infoTitle['unit'].">";
		}
		
		$infoHint = $this->arrData['infoHint'];
		if (!empty($infoHint))
			$data['infoHint'] = $infoHint;
		
		$data['isExcludePage'] = false;
		global $objPage;
		$currentPageId = $objPage->id;
		$excludePages = StringUtil::deserialize($this->arrData['excludePages']);
		foreach ($excludePages as $excludePage) {
			if ($currentPageId == $excludePage) {
				$data['isExcludePage'] = true;
			}
		}
		
		$data['saveButton'] = $this->__get('saveButton');
		$data['saveAllButton'] = $this->__get('saveAllButton');
		
		$data['animation'] = '';
		if (!empty($this->__get('animation')))
			$data['animation'] = $this->__get('animation');
		
		$data['position'] = $this->__get('position');

        $data['highlightSaveAllButton'] = $this->__get('highlightSaveAllButton');

		$this->Template->setData($data);
	}
	
	/**
	 * @param ContainerInterface $container
	 * @return mixed
	 */
	public static function getCookieData(ContainerInterface $container) {
		
		$request = $container->get('request_stack');
		$cookies = $request->getCurrentRequest()->cookies;
		
		$netzhirschOptInCookie = $cookies->get('_netzhirsch_cookie_opt_in');
		
		/** @noinspection PhpComposerExtensionStubsInspection */
		return json_decode($netzhirschOptInCookie);
	}
	
	/**
	 * @param $defaultCss
	 * @param $cssTemplateStyle
	 * @param $maxWidth
	 *
	 * @throws Less_Exception_Parser
	 */
	public static function setCssJs($defaultCss,$cssTemplateStyle,$maxWidth)
	{
		
		if ($defaultCss == "1") {
			$path = dirname(__DIR__,5).DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'bundles'.DIRECTORY_SEPARATOR.'netzhirschcookieoptin'.DIRECTORY_SEPARATOR;
			if (!file_exists($path.'netzhirschCookieOptIn.css')) {
				Helper::parseLessToCss('netzhirschCookieOptIn.less','netzhirschCookieOptIn.css',$maxWidth);
			}
			$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptIn.css|static';
			
			if ($cssTemplateStyle == 'dark'){
				if (!file_exists($path.'netzhirschCookieOptInDarkVersion.css')) {
					Helper::parseLessToCss('netzhirschCookieOptInDarkVersion.less','netzhirschCookieOptInDarkVersion.css',$maxWidth);
				}
				$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInDarkVersion.css|static';
			} elseif 	($cssTemplateStyle == 'light') {
				if (!file_exists($path.'netzhirschCookieOptInLightVersion.css')) {
					Helper::parseLessToCss('netzhirschCookieOptInLightVersion.less','netzhirschCookieOptInLightVersion.css',$maxWidth);
				}
				$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInLightVersion.css|static';
			}
		}
		$jqueryIsLoaded = false;
		foreach ($GLOBALS['TL_JAVASCRIPT'] as $javascript) {
			if (strrpos($javascript,"jquery.min.js") !== false){
				$jqueryIsLoaded = true;
			}
		}
		if (!$jqueryIsLoaded) {
			$GLOBALS['TL_JAVASCRIPT']['jquery'] = 'bundles/netzhirschcookieoptin/jquery.min.js|static';
		}
		$GLOBALS['TL_JAVASCRIPT']['netzhirsch'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptIn.js|static';
	}
}