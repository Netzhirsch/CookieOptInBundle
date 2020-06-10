<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Less_Exception_Parser;
use Netzhirsch\CookieOptInBundle\Classes\Helper;
use Netzhirsch\CookieOptInBundle\Controller\CookieController;
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
		
		if (PageLayoutListener::doNotTrackBrowserSetting($this->id))
			return null;
		$netzhirschOptInCookie = self::getCookieData(System::getContainer(),$this->id);
        $data['cookieGroupsSelected'] = [];
        if (!empty($netzhirschOptInCookie->groups))
		    $data['cookieGroupsSelected'] = $netzhirschOptInCookie->groups;

        $data['noScriptTracking'] = [];
		global $objPage;
        $groups = $this->__get('cookieGroups');
        $groups = StringUtil::deserialize($groups);
        $data['cookieGroups'] = [
            0 => [
                'technicalName' => $groups[0]['key'],
                'name' => $groups[0]['value']
            ]
        ];
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
            $technicalName = null;
            $name = null;
            foreach ($groups as $group) {
                if ($group['key'] == $cookieTool->cookieToolGroup) {
                    $technicalName = $group['key'];
                    $name = $group['value'];
                    $cookieTool->cookieToolGroupName = $name;
                }
            }
            if (!empty($technicalName) && !empty($name)) {
                $newGroup = true;
                foreach ($data['cookieGroups'] as $cookieGroup) {
                    if ($cookieGroup['technicalName'] == $technicalName) {
                        $newGroup = false;
                    }
                }
                if ($newGroup) {
                    $data['cookieGroups'][] = [
                        'technicalName' => $technicalName,
                        'name' => $name
                    ];
                }
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
			$data['imprint'] = '<a class="ncoi---link" href="'.$imprint->getFrontendUrl().'" title ="'.$imprint->title.'"> '.$imprint->title.' </a>';
		}

		$privacyPolicy = PageModel::findById($this->__get('privacyPolicy'));
		if (!empty($privacyPolicy)) {
			$data['privacyPolicy'] = '<a class="ncoi---link" href="'.$privacyPolicy->getFrontendUrl().'" title ="'.$privacyPolicy->title.'"> '.$privacyPolicy->title.' </a>';
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

        /********* load $GLOBALS['TL_LANG']['tl_module'] **************************************************************/
		System::loadLanguageFile('tl_module');

        $infoButtonShow = $this->__get('infoButtonShow');
        if (empty($infoButtonShow))
            $infoButtonShow = $GLOBALS['TL_LANG']['tl_module']['infoButtonShowDefault'];
		$data['infoButtonShow'] = $infoButtonShow;

        $infoButtonHide = $this->__get('infoButtonHide');
        if (empty($infoButtonHide))
            $infoButtonHide = $GLOBALS['TL_LANG']['tl_module']['infoButtonHideDefault'];
        $data['infoButtonHide'] = $infoButtonHide;

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
	public static function getCookieData(ContainerInterface $container,$modId = null) {
		
        $requestStack = $container->get('request_stack');
        $request = $requestStack->getCurrentRequest();
		$cookies = $request->cookies;
        /* @var Connection $conn */
        $conn = $container->get('database_connection');
        if (empty($modId))
            $modId = $request->get('data')['modId'];
        $optInTechnicalName = CookieController::getOptInTechnicalCookieName($conn,$modId);
        $optInTechnicalName = $cookies->get($optInTechnicalName);
		
		/** @noinspection PhpComposerExtensionStubsInspection */
		return json_decode($optInTechnicalName);
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