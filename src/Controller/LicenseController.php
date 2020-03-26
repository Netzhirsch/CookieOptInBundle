<?php


namespace Netzhirsch\CookieOptInBundle\Controller;


use Contao\Config;
use Netzhirsch\CookieOptInBundle\Classes\LicenseAPIResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Contao\PageModel;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao", defaults={
 *     "_scope" = "backend",
 *     "_token_check" = true,
 * })
 */
class LicenseController extends AbstractController
{
	/**
	 * @Route("/license", name="app.license")
	 * @return RedirectResponse
	 * @throws Exception
	 */
	public function licenseAction() {

		$rootPages = PageModel::findByType('root');
		foreach ($rootPages as $rootPage) {
			if (!empty($rootPage->__get('ncoi_license_key'))) {
			    $domain = ($rootPage->__get('dns')) ? $rootPage->__get('dns') : $_SERVER['HTTP_HOST'];
				$licenseAPIResponse = self::callAPI($domain);
				if ($licenseAPIResponse->getSuccess())
					self::setLicense($licenseAPIResponse->getDateOfExpiry(), $licenseAPIResponse->getLicenseKey(), $rootPage);
			}
		}

		$licenseKey = Config::get('ncoi_license_key');
		if (!empty($licenseKey)) {
			$licenseAPIResponse = self::callAPI($_SERVER['HTTP_HOST']);
			if ($licenseAPIResponse->getSuccess())
				self::setLicense($licenseAPIResponse->getDateOfExpiry(),$licenseAPIResponse->getLicenseKey());
		}

		/** @noinspection PhpParamsInspection */
		return $this->redirectToRoute('contao_backend');
	}

	/**
	 * @param           $licenseExpiryDate
	 * @param           $licenseKey
	 * @param PageModel $rootPage
	 *
	 */
	public static function setLicense($licenseExpiryDate,$licenseKey,$rootPage = null) {

		if (empty($licenseExpiryDate))
			return;

		if (empty($rootPage)) {
			Config::persist('ncoi_license_key', $licenseKey);
			Config::persist('ncoi_license_expiry_date', $licenseExpiryDate);
		} else {
			$rootPage->__set('ncoi_license_key',$licenseKey);
			$rootPage->__set('ncoi_license_expiry_date',$licenseExpiryDate);
			$rootPage->save();
		}
	}

	/** @noinspection PhpComposerExtensionStubsInspection ext-curl,ext-json is required in bundle composer.json phpStorm don't check that*/
	/**
	 * @param $domain
	 *
	 * @return LicenseAPIResponse
	 */
	public static function callAPI($domain) {
		$licenseAPIResponse = new LicenseAPIResponse();

		$curl = curl_init('https://buero.netzhirsch.de/license/verify/' . $domain);
		//response as string
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//to set HTTP-POST-request
		curl_setopt($curl, CURLOPT_POST, true);
		$response = curl_exec($curl);
		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
			$response = json_decode($response);
			if ($response->success) {
				$licenseAPIResponse->setSuccess(true);
				$licenseAPIResponse->setDateOfExpiry($response->dateOfExpiry);
				$licenseAPIResponse->setLicenseKey($response->licenseKey);
			}
		}

		curl_close($curl);
		return $licenseAPIResponse;
	}

	/**
	 * @param          $domain
	 * @param $licenseExpiryDate
	 *
	 * @return string
	 */
	public static function getHash($domain,$licenseExpiryDate){
		return hash('sha256',$domain.'-'.$licenseExpiryDate);
	}
}