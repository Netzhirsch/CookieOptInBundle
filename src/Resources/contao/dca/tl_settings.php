<?php

use Netzhirsch\CookieOptInBundle\Controller\LicenseController;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;

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
				'alwaysSave' => true,
				'submitOnChange' => true,
		],
        'sql'       => "varchar(64) NOT NULL default ''",
		'load_callback' => [['tl_settings','getLicenseKey']]
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
			'sql' => "varchar(64) NULL NULL default ''",
			'load_callback' => [['tl_settings','getLicenseExpiryDate']]
	],
];

$dc['fields'] = array_merge($dc['fields'], $arrFields);

class tl_settings {
	/**
	 * @param $licenseExpiryDate
	 *
	 * @return DateTime|string|null
	 * @throws Exception
	 */
	public function getLicenseExpiryDate($licenseExpiryDate) {
		$licenseKey = Config::get('ncoi_license_key');
		if (empty($licenseKey))
			$licenseExpiryDate = PageLayoutListener::getTrialPeriod()->format('d.m.Y');

		if (!empty($licenseKey)) {

			$response = LicenseController::callAPI($_SERVER['HTTP_HOST']);
			if ($response !== 'false') {
				$licenseExpiryDate = $response->dateOfExpiry;

				$licenseExpiryDate =  date_create_from_format('Y-m-d', $licenseExpiryDate);
				// real in settings
				Config::persist('ncoi_license_expiry_date', $licenseExpiryDate->format('Y-m-d'));

				// just view in settings
				return $licenseExpiryDate->format('d.m.Y');
			} else {
				$licenseExpiryDate = PageLayoutListener::getTrialPeriod()->format('d.m.Y');
			}
		}

		return $licenseExpiryDate;
	}

	public function getLicenseKey($licenseKey) {
		if (!empty($licenseKey)){

			$response = LicenseController::callAPI($_SERVER['HTTP_HOST']);
			if ($response !== 'false') {
				$licenseKey = $response->licenseKey;

				// real in settings
				Config::persist('ncoi_license_key', $licenseKey);

				// just view in settings
				return $licenseKey;
			} else {
				$licenseKey = '';
			}
		}

		return $licenseKey;
	}
}