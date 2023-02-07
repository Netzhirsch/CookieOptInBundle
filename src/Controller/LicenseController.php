<?php


namespace Netzhirsch\CookieOptInBundle\Controller;


use Contao\Config;
use DateTime;
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
			    $domain = ($rootPage->__get('dns')) ? $rootPage->__get('dns') : $_SERVER['SERVER_NAME'];
				$licenseAPIResponse = self::callAPI($domain,false);
				if ($licenseAPIResponse->getSuccess())
					self::setLicense($licenseAPIResponse->getDateOfExpiry(), $licenseAPIResponse->getLicenseKey(), $rootPage);
			}
		}

		$licenseKey = Config::get('ncoi_license_key');
		if (!empty($licenseKey)) {
			$licenseAPIResponse = self::callAPI($_SERVER['SERVER_NAME'],false);
			if ($licenseAPIResponse->getSuccess())
				self::setLicense($licenseAPIResponse->getDateOfExpiry(),$licenseAPIResponse->getLicenseKey());
		}

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
     * @param $isFrontendCall
     * @return LicenseAPIResponse
     */
	public static function callAPI($domain,$isFrontendCall) {

	    $licenseAPIResponse = new LicenseAPIResponse();

	    if ($isFrontendCall) {
            $licenseInPage = true;
            if (!empty(Config::get('ncoi_license_key')))
                $licenseInPage = false;


            $today = (new DateTime())->format('Y-m-d');
            if ($licenseInPage) {
                $pages = PageModel::findByDNs($domain);
                if (empty($pages))
                    return $licenseAPIResponse;

                $lastLicenseCheck = $pages->__get('ncoi_last_license_check');
                if (empty($lastLicenseCheck)) {
                    $pages->__set('ncoi_last_license_check',$today);
                    $pages->save();
                    return $licenseAPIResponse;
                }
            } else {
                $lastLicenseCheck = Config::get('ncoi_last_license_check');
                if (empty($lastLicenseCheck)) {
                    Config::persist('ncoi_last_license_check',$today);
                    return $licenseAPIResponse;
                }
            }

            if ($lastLicenseCheck == $today)
                return $licenseAPIResponse;
        }

		$curl = curl_init('https://buero.netzhirsch.de/license/verify/' . $domain);
		//response as string
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
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