<?php

use Contao\Config;
use Netzhirsch\CookieOptInBundle\Controller\LicenseController;

$dc = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palettes
 */
$dc['palettes']['default'] = str_replace('{chmod_legend', '{ncoi_license_legend},ncoi_license_key,ncoi_license_expiry_date,ncoi_license_protected;{chmod_legend', $dc['palettes']['default']);

   
/**
 * Fields
 */
$arrFields = [
    'ncoi_license_key' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_settings']['ncoi_license_key'],
        'exclude'   => true,
        'inputType' => 'text',
        'eval'      => [
				'rgxp'=>'alnum',
				'tl_class' => 'w50',
				'alwaysSave' => true
		],
        'sql'       => "varchar(64) NOT NULL default ''",
		'save_callback' => [['tl_settings_ncoi','saveLicenseData']]
    ],
	'ncoi_license_expiry_date' => [
			'label' => &$GLOBALS['TL_LANG']['tl_settings']['ncoi_license_expiry_date'],
			'exclude'   => true,
			'inputType' => 'text',
			'eval' => [
					'tl_class' => 'w50',
					'alwaysSave' => true,
					'readonly' => true
			],
			'sql' => "varchar(64) NULL NULL default ''"
	],
    'ncoi_last_license_check' => [
        'sql' => "varchar(64) NULL NULL default ''"
    ]
];

$dc['fields'] = array_merge($dc['fields'], $arrFields);

class tl_settings_ncoi {

	public function saveLicenseData($licenseKey) {
		if (!empty($licenseKey)){
            $domain = $_SERVER['SERVER_NAME'];
			$licenseAPIResponse = LicenseController::callAPI($domain,false);
			if ($licenseAPIResponse->getSuccess()) {
				$licenseKey = $licenseAPIResponse->getLicenseKey();
				$licenseExpiryDate = $licenseAPIResponse->getDateOfExpiry();

				Config::persist('ncoi_license_key', $licenseKey);
				Config::persist('ncoi_license_expiry_date', $licenseExpiryDate);
				return $licenseKey;
			}
		}

		Config::persist('ncoi_license_key', "");
		Config::persist('ncoi_license_expiry_date', "");
		return "";
	}
}