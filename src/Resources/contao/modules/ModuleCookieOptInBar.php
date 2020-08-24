<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Less_Exception_Parser;
use Netzhirsch\CookieOptInBundle\Classes\Helper;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;

class ModuleCookieOptInBar extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cookie_opt_in_bar';

    /**
     * @return string
     * @throws DBALException|Less_Exception_Parser
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

        $conn = System::getContainer()->get('database_connection');
        $sql = "SELECT defaultCss,cssTemplateStyle,blockSite,zIndex,maxWidth,respectToNotTrack 
                FROM tl_ncoi_cookie 
                WHERE pid = ?
        ";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $this->__get('id'));
        $stmt->execute();
        $result = $stmt->fetch();

        $this->setCss(
            $result['defaultCss'],
            $result['cssTemplateStyle'],
            $result['maxWidth'],
            $result['blockSite'],
            $result['zIndex']
        );
        $this->setJs();

        if (PageLayoutListener::doNotTrackBrowserSetting($result['respectToNotTrack']))
            return null;

		return parent::generate();
	}

    /**
     * @throws Less_Exception_Parser
     * @throws DBALException
     */
	public function compile(){

        $conn = System::getContainer()->get('database_connection');
        $sql = "SELECT * FROM tl_ncoi_cookie WHERE pid = ?";
        /** @var Statement $stmt */
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $this->__get('id'));
        $stmt->execute();
        $result = $stmt->fetch();
        $maxWidth = $result['maxWidth'];

		$this->strTemplate = 'mod_cookie_opt_in_bar';
		$this->Template = new FrontendTemplate($this->strTemplate);
		$data = $this->Template->getData();

        $data['id'] = $this->id;


        $data['cookieVersion'] = 1;
        if (isset($result['cookieVersion']) && !empty($result['cookieVersion']))
            $data['cookieVersion'] = $result['cookieVersion'];

        //********* noscript ******************************************************************************************/
        $data['noscript'] = false;
        $ncoiSession = null;
        $data['cookiesSelected'] = [];
        if (
            isset($_SESSION)
            && isset($_SESSION['_sf2_attributes'])
            && isset($_SESSION['_sf2_attributes']['ncoi'])
            && !empty($_SESSION['_sf2_attributes']['ncoi']['expireTime'])
            && ($_SESSION['_sf2_attributes']['ncoi']['expireTime'] >= date('Y-m-d'))
        ) {
            $data['noscript'] = true;
            $ncoiSession = $_SESSION['_sf2_attributes']['ncoi'];
            if (!empty($ncoiSession['cookieIds']))
                $data['cookiesSelected'] = $ncoiSession['cookieIds'];
        }

        $data['inconspicuous'] = false;
        $array = StringUtil::deserialize($maxWidth);
        if ($array['value'] == '100' && $array['unit'] == '%') {
            $data['inconspicuous'] = true;
        }

		global $objPage;
        $groups = $result['cookieGroups'];
        $groups = StringUtil::deserialize($groups);
        $data['cookieGroups'] = [
            0 => [
                'technicalName' => $groups[0]['key'],
                'name' => $groups[0]['value']
            ]
        ];

		$data['cookieTools'] = FieldPaletteModel::findByPid($this->id);
        $data['noScriptTracking'] = [];
        $data['cookieGroupsSelected'] = [1];
		foreach ($data['cookieTools'] as $cookieTool) {
            if (!empty($ncoiSession) && $data['noscript']) {
                foreach ($ncoiSession['cookieIds'] as $cookieId) {
                    if ($cookieId == $cookieTool->id ) {
                        if (!in_array($cookieId,$data['cookieGroupsSelected']))
                            $data['cookieGroupsSelected'][] = $cookieTool->cookieToolGroup;

                        if ($cookieTool->cookieToolsSelect == 'facebookPixel') {
                            $data['noScriptTracking'][] = '<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='.$cookieTool->cookieToolsTrackingId.'&ev=PageView&noscript=1"/>';
                        } elseif ($cookieTool->cookieToolsSelect == 'googleAnalytics') {
                            $data['noScriptTracking'][] = '<img src="http://www.google-analytics.com/collect?v=1&t=pageview&tid='.$cookieTool->cookieToolsTrackingId.'&cid=1&dp='.$objPage->mainAlias.'">';
                        } elseif ($cookieTool->cookieToolsSelect == 'matomo') {
                            $data['noScriptTracking'][] = '<img src="'.$cookieTool->cookieToolsTrackingServerUrl.'/matomo.php?idsite='.$cookieTool->cookieToolsTrackingId.'&amp;rec=1" style="border:0" alt="" />';
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

        $headlineData = StringUtil::deserialize($result['headlineCookieOptInBar']);
		if (!empty($headlineData['value'])) {
			$data['headlineCookieOptInBar'] = "<".$headlineData['unit']." class=\"ncoi---headline\">".$headlineData['value']."</".$headlineData['unit'].">";
		}

		$questionHint = $result['questionHint'];
		if (!empty($questionHint))
			$data['questionHint'] = $questionHint;

		$imprint = PageModel::findById($result['imprint']);
		if (!empty($imprint)) {
			$data['imprint'] = '<a class="ncoi---link" href="'.$imprint->getFrontendUrl().'" title ="'.$imprint->title.'"> '.$imprint->title.' </a>';
		}

		$privacyPolicy = PageModel::findById($result['privacyPolicy']);
		if (!empty($privacyPolicy)) {
			$data['privacyPolicy'] = '<a class="ncoi---link" href="'.$privacyPolicy->getFrontendUrl().'" title ="'.$privacyPolicy->title.'"> '.$privacyPolicy->title.' </a>';
		}

		$infoTitle = $result['infoTitle'];
		if (!empty($infoTitle)) {
			$infoTitle = StringUtil::deserialize($infoTitle);
			$data['infoTitle'] = "<".$infoTitle['unit'].">".$infoTitle['value']."</".$infoTitle['unit'].">";
		}

		$infoHint = $result['infoHint'];
		if (!empty($infoHint))
			$data['infoHint'] = $infoHint;

		$data['isExcludePage'] = false;
		$currentPageId = $objPage->id;
		$data['currentPage'] = $_SERVER['REDIRECT_URL'];
		$excludePages = StringUtil::deserialize($result['excludePages']);
        if (!empty($excludePages)) {
            foreach ($excludePages as $excludePage) {
                if ($currentPageId == $excludePage) {
                    $data['isExcludePage'] = true;
                }
            }
        }

		$data['saveButton'] = $result['saveButton'];
		$data['saveAllButton'] = $result['saveAllButton'];

        /********* load $GLOBALS['TL_LANG']['tl_module'] **************************************************************/
		System::loadLanguageFile('tl_module');

        $infoButtonShow = $result['infoButtonShow'];
        if (empty($infoButtonShow))
            $infoButtonShow = $GLOBALS['TL_LANG']['tl_module']['infoButtonShowDefault'];
		$data['infoButtonShow'] = $infoButtonShow;

        $infoButtonHide = $result['infoButtonHide'];
        if (empty($infoButtonHide))
            $infoButtonHide = $GLOBALS['TL_LANG']['tl_module']['infoButtonHideDefault'];
        $data['infoButtonHide'] = $infoButtonHide;

		$data['animation'] = '';
		if (!empty($result['animation']))
			$data['animation'] = $result['animation'];
		
		$data['position'] = $result['position'];

        $data['highlightSaveAllButton'] = $result['highlightSaveAllButton'];

		$this->Template->setData($data);
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
	private function setCss($defaultCss, $cssTemplateStyle, $maxWidth, $blockSite, $zIndex)
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
	}

    private function setJs()
    {

        $jqueryIsLoaded = false;
        foreach ($GLOBALS['TL_JAVASCRIPT'] as $javascript) {
            if (strrpos($javascript,"jquery.min.js") !== false){
                $jqueryIsLoaded = true;
            }
        }
        if (!$jqueryIsLoaded) {
            $GLOBALS['TL_JAVASCRIPT']['jquery'] = 'bundles/netzhirschcookieoptin/jquery.min.js|static';
        }
        $netzhirschCookieOptInJs = false;
        $cookieJs = false;
        foreach ($GLOBALS['TL_JAVASCRIPT'] as $javascript) {
            if (strrpos($javascript,"netzhirschCookieOptIn.js") !== false){
                $netzhirschCookieOptInJs = true;
            }
            if (strrpos($javascript,"cookie.min.js") !== false){
                $netzhirschCookieOptInJs = true;
            }
        }
        if (!$netzhirschCookieOptInJs) {
            $GLOBALS['TL_JAVASCRIPT']['netzhirsch'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptIn.js|static';
        }
        if (!$cookieJs) {
            $GLOBALS['TL_JAVASCRIPT']['cookieJs'] = 'bundles/netzhirschcookieoptin/library/cookie.min.js|static';
        }
	}
}