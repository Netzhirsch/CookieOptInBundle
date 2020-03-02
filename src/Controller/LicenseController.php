<?php


namespace Netzhirsch\CookieOptInBundle\Controller;


use Contao\Config;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Contao\PageModel;
use DateTime;
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
		$licenseKey = Config::get('ncoi_license_key');
		$licenseExpiryDate = Config::get('ncoi_license_expiry_date');
		if (empty($licenseKey) && empty($licenseExpiryDate)) {
			$rootPages = PageModel::findByType('root');
			foreach ($rootPages as $rootPage) {
				$domain = $rootPage->__get('dns');
				$licenseExpiryDate = self::callAPI($domain);
				self::setLicense($licenseExpiryDate,$domain,$rootPage);
			}
		} else {
			$licenseExpiryDate = self::callAPI($_SERVER['HTTP_HOST']);
			self::setLicense($licenseExpiryDate,$_SERVER['HTTP_HOST']);
		}

		/** @noinspection PhpParamsInspection */
		return $this->redirectToRoute('contao_backend');
	}

	/**
	 * @param $licenseExpiryDate
	 * @param $domain
	 * @param PageModel $rootPage
	 *
	 * @throws Exception
	 */
	public static function setLicense($licenseExpiryDate,$domain,$rootPage = null) {

		if (!empty($licenseExpiryDate)) {
			$date = new DateTime();
			$licenseExpiryDate = $date->setTimestamp($licenseExpiryDate);

			$licenseKey = LicenseController::getHash($domain, $licenseExpiryDate);

			if (empty($rootPage)) {
				Config::persist('ncoi_license_key', $licenseKey);
				Config::persist('ncoi_license_expiry_date', $licenseExpiryDate->format('Y-m-d'));
			} else {
				$rootPage->__set('ncoi_license_key',$licenseKey);
				$rootPage->__set('ncoi_license_expiry_date',$licenseExpiryDate->format('Y-m-d'));
				$rootPage->save();
			}
		}
	}

	public static function callAPI($domain) {
		$response = null;
		$curl = curl_init('https://buero.netzhirsch.de/license/verify/'.$domain);
		//response as string
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//to set HTTP-POST-request
		curl_setopt($curl, CURLOPT_POST, true);
		$response = curl_exec($curl);
		if ($response !== "false") {
			$response = json_decode($response);
		}

		curl_close($curl);
		return $response;
	}

	/**
	 * @param          $domain
	 * @param DateTime $licenseExpiryDate
	 *
	 * @return string
	 */
	public static function getHash($domain,DateTime $licenseExpiryDate){
		return hash('sha256',$domain.'-'.$licenseExpiryDate->format('Y-m-d'));
	}
}