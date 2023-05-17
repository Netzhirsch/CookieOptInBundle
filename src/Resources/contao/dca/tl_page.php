<?php
/**
 * Extend default palette
 */
//Contao 4.9 need rootfallback and root
use Contao\Config;
use Contao\PageModel;
use Netzhirsch\CookieOptInBundle\Classes\Helper;
use Netzhirsch\CookieOptInBundle\Controller\LicenseController;
use Netzhirsch\CookieOptInBundle\EventListener\GetSystemMessagesListener;

// on root page
if (empty($GLOBALS['_GET']))
	$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.css|static';
else
	$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackendPage.css|static';
	
$root = 'rootfallback';

$replace = '{ncoi_license_legend},bar_disabled,ncoi_license_key,ncoi_license_expiry_date,ncoi_license_protected,imprint,privacyPolicy;{global_legend';
$search = '{global_legend';

$GLOBALS['TL_DCA']['tl_page']['palettes'][$root] = str_replace($search, $replace, $GLOBALS['TL_DCA']['tl_page']['palettes'][$root]);

//Contao 4.4 need root, rootfallback will be ignored
$root = 'root';
if (isset($GLOBALS['TL_DCA']['tl_page']['palettes'][$root]))
    $GLOBALS['TL_DCA']['tl_page']['palettes'][$root] = str_replace($search, $replace, $GLOBALS['TL_DCA']['tl_page']['palettes'][$root]);

/**
 * Add fields to tl_page
 */
$arrFields = [
		'ncoi_license_key' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_page']['ncoi_license_key'],
				'exclude'   => true,
				'inputType' => 'text',
				'eval'      => [
					'tl_class' => 'w50'
				],
				'sql'       => "varchar(64) NOT NULL default ''",
				'save_callback' => [['tl_page_ncoi','saveLicenseData']]
		],
		'ncoi_license_expiry_date' => [
			'label' => &$GLOBALS['TL_LANG']['tl_page']['ncoi_license_expiry_date'],
			'exclude'   => true,
			'inputType' => 'text',
			'eval' => [
					'tl_class' => 'w50',
					'readonly' => true
			],
			'sql' => "varchar(64) NULL",
		],
        'bar_disabled' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['bar_disabled'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'long clr',
            ],
            'sql' => "tinyint(1) NULL",
        ],
        'imprint' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['imprint'],
            'exclude'   => true,
            'inputType' => 'pageTree',
            'eval' => [
                'tl_class'  =>  'w50'
            ],
            'sql' => "varchar(255) NULL",
        ],
        'privacyPolicy' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['privacyPolicy'],
            'exclude'   => true,
            'inputType' => 'pageTree',
            'eval' => [
                'tl_class'  =>  'w50'
            ],
            'sql' => "varchar(255) NULL",
        ],
        'ncoi_last_license_check' => [
            'sql' => "varchar(64) NULL "
        ]
];

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = ['tl_page_ncoi','showLicenseWarning'];

$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_page']['fields'], $arrFields);

class tl_page_ncoi extends tl_page {

	/**
	 * @param          $licenseKey
	 * @param DC_Table $dca
	 *
	 * @return string
	 */
	public function saveLicenseData($licenseKey, DC_Table $dca) {
		if (in_array($dca->id, $dca->rootIds)) {
			$pageModel = PageModel::findById($dca->id);

			if (!empty($licenseKey)) {
				$domain = $pageModel->__get('dns');
				if (empty($domain))
					$domain = $_SERVER['SERVER_NAME'];

				$licenseAPIResponse = LicenseController::callAPI($domain,false);

				if ($licenseAPIResponse->getSuccess()) {
					$licenseExpiryDate = $licenseAPIResponse->getDateOfExpiry();
					$licenseKey = $licenseAPIResponse->getLicenseKey();

					$pageModel->__set('ncoi_license_key',$licenseKey);
					$pageModel->__set('ncoi_license_expiry_date',$licenseExpiryDate);
					$pageModel->save();
				}
			} else {
				$pageModel->__set('ncoi_license_key','');
				$pageModel->__set('ncoi_license_expiry_date', '');
				$pageModel->save();
			}
		}
		return $licenseKey;
	}

	/**
	 * @throws Exception
	 */
	public function showLicenseWarning() {
		if (Contao\Input::get('act') != '')
			return;

        if (!Helper::isAdmin())
            return;

		$rootPoints = PageModel::findByType('root');
		$message = '';
		$domainNoDuplicate = [];
		$licenseExpiryDates = [];
		$licenseKeys = [];
		foreach ($rootPoints as $rootPoint) {

            if (!$rootPoint->__get('bar_disabled')) {

                $domain = (!empty($rootPoint->__get('dns'))) ? $rootPoint->__get('dns') : $_SERVER['SERVER_NAME'];
                if (!in_array($domain, $domainNoDuplicate)) {
                    $domainNoDuplicate[] = $domain;
                }
                $licenseKeys[$domain] = (empty($rootPoint->__get('ncoi_license_key'))) ? Config::get('ncoi_license_key') : $rootPoint->__get('ncoi_license_key');
                $licenseExpiryDates[$domain] = (empty($rootPoint->__get('ncoi_license_expiry_date'))) ? Config::get('ncoi_license_expiry_date') : $rootPoint->__get('ncoi_license_expiry_date');
            }

		}

        if (!empty($domainNoDuplicate)) {
            foreach ($domainNoDuplicate as $domain) {
                if (empty($licenseExpiryDates[$domain]))
                    $message .=
                        '<div class="ncoi---backend--message-page">'
                        .GetSystemMessagesListener::getMessage($licenseKeys[$domain],$licenseExpiryDates[$domain],$domain).'</div>';
            }
        }

		if (empty($message))
			return;

		Contao\Message::addRaw($message);

	}
}

