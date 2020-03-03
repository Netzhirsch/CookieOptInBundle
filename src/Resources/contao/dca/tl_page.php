<?php
/**
 * Extend default palette
 */
//Contao 4.9 need rootfallback and root
use Contao\PageModel;
use Netzhirsch\CookieOptInBundle\Controller\LicenseController;
use Netzhirsch\CookieOptInBundle\EventListener\GetSystemMessagesListener;

$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.css|static';

$root = 'rootfallback';

$replace = '{ncoi_license_legend},ncoi_license_key,ncoi_license_expiry_date,ncoi_license_protected;{global_legend';
$search = '{global_legend';

$GLOBALS['TL_DCA']['tl_page']['palettes'][$root] = str_replace($search, $replace, $GLOBALS['TL_DCA']['tl_page']['palettes'][$root]);

//Contao 4.4 need root, rootfallback will be ignored
$root = 'root';
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
					'tl_class' => 'w50',
					'submitOnChange' => true,
				],
				'sql'       => "varchar(64) NOT NULL default ''",
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
];

$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] = [['tl_page_extend','getLicenseData']];

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = ['tl_page_extend','showLicenseWarning'];

$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_page']['fields'], $arrFields);

class tl_page_extend extends tl_page {

	/**
	 * @param DC_Table $dca
	 *
	 * @throws Exception
	 */
	public function getLicenseData(DC_Table $dca) {

		if (in_array($dca->id, $dca->rootIds)) {

			$pageModel = PageModel::findById($dca->id);
			$licenseKey = $pageModel->__get('ncoi_license_key');
			$licenseExpiryDate = null;

			if (!empty($licenseKey)) {
				$domain = $pageModel->__get('dns');
				if (empty($domain))
					$domain = $_SERVER['HTTP_HOST'];

				$licenseAPIResponse = LicenseController::callAPI($domain);

				if ($licenseAPIResponse->getSuccess()) {
					$licenseExpiryDate = $licenseAPIResponse->getDateOfExpiry();
					$licenseKey = $licenseAPIResponse->getLicenseKey();

					$pageModel->__set('ncoi_license_key',$licenseKey);
					$pageModel->__set('ncoi_license_expiry_date',$licenseExpiryDate);
					$pageModel->save();

				}
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function showLicenseWarning() {
		if (Contao\Input::get('act') != '')
		{
			return;
		}
		$rootPoints = PageModel::findByType('root');
		$message = '';
		foreach ($rootPoints as $rootPoint) {

			$licenseKey = $rootPoint->__get('ncoi_license_key');
			$licenseExpiryDate = $rootPoint->__get('ncoi_license_expiry_date');

			$domain = (!empty($rootPoint->__get('dns'))) ? $rootPoint->__get('dns') : $_SERVER['HTTP_HOST'];
			$message .= '<div class="ncoi---backend--message-page">'.GetSystemMessagesListener::getMessage($licenseKey,$licenseExpiryDate,$domain).'</div>';

		}
		if (empty($message))
			return;

		Contao\Message::addRaw($message);

	}
}

