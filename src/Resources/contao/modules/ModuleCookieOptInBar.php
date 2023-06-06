<?php

namespace Netzhirsch\CookieOptInBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Less_Exception_Parser;
use Netzhirsch\CookieOptInBundle\Classes\Helper;
use Netzhirsch\CookieOptInBundle\Repository\LayoutRepository;
use Netzhirsch\CookieOptInBundle\Repository\Repository;

class ModuleCookieOptInBar extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cookie_opt_in_bar';

    /**
     * @return string
     * @throws Less_Exception_Parser
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

        $strQuery = "SELECT defaultCss,cssTemplateStyle,blockSite,zIndex,maxWidth,respectDoNotTrack
                FROM tl_ncoi_cookie
                WHERE pid = ?
        ";

        $repo = new Repository($this->Database);
        $result = $repo->findRow($strQuery,[], [$this->__get('id')]);

        $this->setCss(
            $result['defaultCss'],
            $result['cssTemplateStyle'],
            $result['maxWidth'],
            $result['blockSite'],
            $result['zIndex']
        );
        $this->setJs();

		return parent::generate();
	}

	public function compile(){

        $conn = $this->Database;
        $repo = new Repository($conn);
        $strQuery = "SELECT * FROM tl_ncoi_cookie WHERE pid = ?";
        $result = $repo->findRow($strQuery,[], [$this->__get('id')]);
        $maxWidth = $result['maxWidth'];

		$this->strTemplate = $result['templateBar'];
		$this->Template = new FrontendTemplate($this->strTemplate);
		$data = $this->Template->getData();
        $data['respectDoNotTrack'] = $result['respectDoNotTrack'];

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

		$data['cookieTools'] = [];
		$unorderedData = FieldPaletteModel::findByPid($this->id,['cookieGroups' => 'DESC']);
        foreach ($unorderedData as $unorderedDatum) {
            $data['cookieTools'][$unorderedDatum->sorting] = $unorderedDatum;
        }
        ksort($data['cookieTools']);
        $unorderedData = $data['cookieTools'];
        $data['cookieTools'] = [];
        foreach ($unorderedData as $unorderedDatum) {
            $data['cookieTools'][$unorderedDatum->cookieToolGroup][] = $unorderedDatum;
        }
        ksort($data['cookieTools']);
        $unorderedData = $data['cookieTools'];
        $data['cookieTools'] = [];
        foreach ($unorderedData as $unorderedDatum) {
            foreach ($unorderedDatum as $tmp) {
                $data['cookieTools'][] = $tmp;
            }
        }

        $data['noScriptTracking'] = [];
        $data['cookieGroupsSelected'] = [1];
        if (!empty($data['cookieTools'])) {
            foreach ($data['cookieTools'] as $cookieTool) {
                if (!empty($ncoiSession) && $data['noscript']) {
                    foreach ($ncoiSession['cookieIds'] as $cookieId) {
                        if ($cookieId == $cookieTool->id) {

                            if (!in_array($cookieId, $data['cookieGroupsSelected'])) {
                                $data['cookieGroupsSelected'][] = $cookieTool->cookieToolGroup;
                            }

                            $cookieToolsSelect = $cookieTool->cookieToolsSelect;
                            if (self::hasTemplate($conn, $objPage->layout, $cookieToolsSelect)) {
                                continue;
                            }

                            $trackingId = $cookieTool->cookieToolsTrackingId;

                            if ($cookieTool->cookieToolsSelect == 'facebookPixel') {
                                $data['noScriptTracking'][] = '<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='.$cookieTool->cookieToolsTrackingId.'&ev=PageView&noscript=1"/>';
                            } elseif ($cookieTool->cookieToolsSelect == 'googleAnalytics') {

                                $data['noScriptTracking'][] = '<img src="https://www.google-analytics.com/collect?v=1&t=pageview&tid='.$trackingId.'&cid=1&dp='.$objPage->mainAlias.'">';
                            } elseif ($cookieTool->cookieToolsSelect == 'matomo') {

                                $data['noScriptTracking'][] = '<img src="'.$cookieTool->cookieToolsTrackingServerUrl.'/matomo.php?idsite='.$trackingId.'&amp;rec=1" style="border:0" alt="" />';
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
                            'name' => $name,
                        ];
                    }
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

        $data['imprint'] = self::getImprint($objPage,$result['imprint']);

        $data['privacyPolicy'] = self::getPrivacyPolicy($objPage,$result['privacyPolicy']);

		$infoTitle = isset($result['infoTitle']) ? $result['infoTitle'] : '';
		if (!empty($infoTitle)) {
			$infoTitle = StringUtil::deserialize($infoTitle);
			$data['infoTitle'] = "<".$infoTitle['unit'].">".$infoTitle['value']."</".$infoTitle['unit'].">";
		}

		$infoHint = $result['infoHint'];
		if (!empty($infoHint))
			$data['infoHint'] = $infoHint;

		$data['isExcludePage'] = false;
		$currentPageId = $objPage->id;
		$data['currentPage'] = (isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '');
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
		$data['rejectAllButton'] = $result['rejectAllButton'];

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

        if ($result['optOut'] == 1)
            $data['optOut'] = 'default';
        else
            $data['optOut'] = 'no';

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
			$path = System::getContainer()->getParameter('contao.web_dir').DIRECTORY_SEPARATOR.'bundles'.DIRECTORY_SEPARATOR.'netzhirschcookieoptin'.DIRECTORY_SEPARATOR;
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

        if (!self::isJqueryLoaded()) {
            $GLOBALS['TL_JAVASCRIPT']['jquery'] = 'bundles/netzhirschcookieoptin/jquery.min.js|static';
        }

        if (!self::isCookieLibLoaded()) {
            $GLOBALS['TL_JAVASCRIPT']['cookieJs'] = 'bundles/netzhirschcookieoptin/library/cookie.min.js|static';
        }

        if (!self::isNcoiJsAlreadyLoaded()) {
            self::loadNcoiJs();
        }
	}

    private static function isJqueryLoaded()
    {
        return self::isJsAlreadyLoaded('jquery.min.js');
	}

    private static function isCookieLibLoaded()
    {
        return self::isJsAlreadyLoaded('cookie.min.js');
	}

    private static function isJsAlreadyLoaded($filename)
    {
        foreach ($GLOBALS['TL_JAVASCRIPT'] as $javascript) {
            if (strrpos($javascript,$filename) !== false){
                return true;
            }
        }
        return false;
	}

    private static function isNcoiJsAlreadyLoaded()
    {
        return self::isJsAlreadyLoaded('NcoiApp.min.js');
	}

    private static function loadNcoiJs()
    {
        $relativDir = self::getRelativDir();
        $files = scandir($relativDir);
        foreach ($files as $file) {
            if (self::isPresentDirOrParentDir($file))
                continue;

            if (self::isFileADir($file)) {
                self::includeFilesInDir($file);
            }
            else {
                if (strpos($file, 'min') !== false)
                    $GLOBALS['TL_JAVASCRIPT'][$file] = $relativDir.DIRECTORY_SEPARATOR.$file.'|static';
            }
        }
	}

    private static function includeFilesInDir($dir)
    {
        $relativPath = self::getRelativDir();
        $templateFiles = scandir(self::getDir().DIRECTORY_SEPARATOR.$dir);
        foreach ($templateFiles as $templateFile) {
            if (self::isPresentDirOrParentDir($templateFile))
                continue;
            $filepath = $relativPath.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$templateFile.'|static';
            if (strpos($filepath, 'min') !== false)
                $GLOBALS['TL_JAVASCRIPT'][$templateFile] = $filepath;
        }
	}

    private static function getRelativDir()
    {
        return 'bundles'.DIRECTORY_SEPARATOR.'netzhirschcookieoptin'.DIRECTORY_SEPARATOR.'js';
	}
    private static function getDir()
    {
        return self::getRootDir().
            DIRECTORY_SEPARATOR.
            'bundles'.
            DIRECTORY_SEPARATOR.
            'netzhirschcookieoptin'.
            DIRECTORY_SEPARATOR.
            'js';
	}
    private static function getRootDir()
    {
        return System::getContainer()->getParameter('contao.web_dir');
	}

    private static function isFileADir($file)
    {
        return is_dir(self::getDir().DIRECTORY_SEPARATOR.$file);
	}

    private static function isPresentDirOrParentDir($file)
    {
        return ($file == '.' || $file == '..');
    }


    private static function hasTemplate($conn,$layout,$name) {

	    if ($name == 'googleAnalytics')
	        $name = 'google';
	    elseif ($name == 'matomo')
            $name = 'piwik';

	    if (empty($name))
	        return false;


        $repo = new LayoutRepository($conn);
        $analytics = $repo->find($layout);

        foreach ($analytics as $analytic) {

            if (isset($analytic['analytics'])) {

                $analyticFile = StringUtil::deserialize($analytic['analytics']);
                $analyticFile = $analyticFile[array_key_first($analyticFile)];
                if (strpos($analyticFile,$name))
                    return true;
            }
        }

        return false;
    }

    private static function getImprint($objPage,$id){

        $rootPage = PageModel::findById($objPage->rootId);
        if (empty($rootPage))
            return '';
        $details = $rootPage->loadDetails();
        if (!empty($details->imprint))
            return self::getLink($details->imprint);

        return self::getLink($id);
    }

    private static function getPrivacyPolicy($objPage,$id){

        $rootPage = PageModel::findById($objPage->rootId);
        if (empty($rootPage))
            return '';
        $details = $rootPage->loadDetails();
        if (!empty($details->privacyPolicy))
            return self::getLink($details->privacyPolicy);

        return self::getLink($id);
    }

    private static function getLink($id){

        $page = PageModel::findById($id);
        if (empty($page))
            return '';

	    return '<a class="ncoi---link" href="'.$page->getFrontendUrl().'" title ="'.$page->title.'"> '.$page->title.' </a>';
    }

}
