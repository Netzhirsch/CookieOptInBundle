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

		$maxWidth = $this->__get('maxWidth');
        $data['inconspicuous'] = false;
        $array = StringUtil::deserialize($maxWidth);
        if ($array['value'] == '100' && $array['unit'] == '%') {
            $data['inconspicuous'] = true;
        }

		self::setCssJs(
		    $this->__get('defaultCss'),
            $this->__get('cssTemplateStyle'),
            $maxWidth,
            $this->__get('blockSite')
            ,$this->__get('zIndex')
        );
		
		$data['cookieTools'] = FieldPaletteModel::findByPid($this->id);
		
		if (PageLayoutListener::doNotTrackBrowserSetting($data['cookieTools'],$this->id))
			return null;
		
		$netzhirschOptInCookie = self::getCookieData(System::getContainer());
        $data['cookieGroupsSelected'] = [];
        if (!empty($netzhirschOptInCookie->groups))
		    $data['cookieGroupsSelected'] = $netzhirschOptInCookie->groups;
		$data['cookieGroupsSelected'][] = $tlLang['cookieGroup']['essential'];
		$data['cookieGroups'][] = $tlLang['cookieGroup']['essential'];

        $data['noScriptTracking'] = [];
		global $objPage;
		foreach ($data['cookieTools'] as $cookieTool) {
            if (!empty($netzhirschOptInCookie)) {
                foreach ($netzhirschOptInCookie->cookieIds as $cookieId) {
                    if ($cookieId == $cookieTool->id) {
                        if (!$netzhirschOptInCookie->isJavaScript) {
                            if ($cookieTool->cookieToolsSelect == 'facebookPixel')
                                $data['noScriptTracking'][] = '<img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id='.$cookieTool->cookieToolsTrackingId.'&ev=PageView&noscript=1"
      />';
                            elseif ($cookieTool->cookieToolsSelect == 'googleAnalytics')
                                $data['noScriptTracking'][] = '<img src="http://www.google-analytics.com/collect?v=1&t=pageview&tid='.$cookieTool->cookieToolsTrackingId.'&cid=1&dp='.$objPage->mainAlias.'">';
                            elseif ($cookieTool->cookieToolsSelect == 'matomo')
                                $data['noScriptTracking'][] = '<img src="'.$cookieTool->cookieToolsTrackingServerUrl.'/matomo.php?idsite='.$cookieTool->cookieToolsTrackingId.'&amp;rec=1" style="border:0" alt="" />;
';
                        }
                        if (!in_array($cookieTool->cookieToolGroup, $data['cookieGroupsSelected'])) {
                            $data['cookieGroupsSelected'][] = $cookieTool->cookieToolGroup;
                        }
                    }
                }
            }
			if (!in_array($cookieTool->cookieToolGroup, $data['cookieGroups']) && $cookieTool->cookieToolGroup != 'essential' && $cookieTool->cookieToolGroup != 'Essenziell') {
				$data['cookieGroups'][] = $cookieTool->cookieToolGroup;
			}

		}

		$data['cookiesSelected'] = [];
        if (!empty($netzhirschOptInCookie->cookieIds))
		    $data['cookiesSelected'] = $netzhirschOptInCookie->cookieIds;
		$data['id'] = $this->id;
		$data['netzhirschCookieIsSet'] = false;
		if (!empty($netzhirschOptInCookie))
			$data['netzhirschCookieIsSet'] = true;

		$data['netzhirschCookieIsVersionNew'] = "0";
		if ($netzhirschOptInCookie->cookieVersion < $this->__get('cookieVersion'))
			$data['netzhirschCookieIsVersionNew'] = "1";

        $headlineData = StringUtil::deserialize($this->headlineCookieOptInBar);
		if (!empty($headlineData['value'])) {
			$data['headlineCookieOptInBar'] = "<".$headlineData['unit']." class=\"ncoi---headline\">".$headlineData['value']."</".$headlineData['unit'].">";
		}

		$questionHint = $this->arrData['questionHint'];
		if (!empty($questionHint))
			$data['questionHint'] = $questionHint;

		$imprint = PageModel::findById($this->__get('imprint'));
		if (!empty($imprint)) {
            $imprintUrl = $imprint->getFrontendUrl();
            $imprint = '<a class="ncoi---link" href="'.$imprintUrl.'" title ="'.$tlLang['imprint'].'"> '.$tlLang['imprint'].' </a>';
			$data['imprint'] = $imprint;
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
		$currentPageId = $objPage->id;
		$data['currentPage'] = $_SERVER['REDIRECT_URL'];
		$excludePages = StringUtil::deserialize($this->arrData['excludePages']);
        if (!empty($excludePages)) {
            foreach ($excludePages as $excludePage) {
                if ($currentPageId == $excludePage) {
                    $data['isExcludePage'] = true;
                }
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
     * @param $blockSite
     * @param $zIndex
     * @throws Less_Exception_Parser
     */
	public static function setCssJs($defaultCss, $cssTemplateStyle, $maxWidth, $blockSite, $zIndex)
	{
		
		if ($defaultCss == "1") {
			$path = dirname(__DIR__,5).DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'bundles'.DIRECTORY_SEPARATOR.'netzhirschcookieoptin'.DIRECTORY_SEPARATOR;
			if (!file_exists($path.'netzhirschCookieOptIn.css')) {
				Helper::parseLessToCss('netzhirschCookieOptIn.less','netzhirschCookieOptIn.css',$maxWidth,$blockSite,$zIndex);
			}
			$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptIn.css|static';
			
			if ($cssTemplateStyle == 'dark'){
				if (!file_exists($path.'netzhirschCookieOptInDarkVersion.css')) {
					Helper::parseLessToCss('netzhirschCookieOptInDarkVersion.less','netzhirschCookieOptInDarkVersion.css');
				}
				$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInDarkVersion.css|static';
			} elseif 	($cssTemplateStyle == 'light') {
				if (!file_exists($path.'netzhirschCookieOptInLightVersion.css')) {
					Helper::parseLessToCss('netzhirschCookieOptInLightVersion.less','netzhirschCookieOptInLightVersion.css');
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