<?php


namespace Netzhirsch\CookieOptInBundle\Controller;

use Contao\Database;
use DateTime;
use Exception;
use Netzhirsch\CookieOptInBundle\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

class ConsentController extends AbstractController
{

    /** @var Database $database */
    private $database;

    public function __construct()
    {

        $this->database = Database::getInstance();
    }

    /**
	 * @Route("/consent/download", name="consent_download",  defaults={"_scope" = "backend"})
	 * @throws Exception
	 */
	public function indexAction()
	{
        $hasBackendUser = $this->getUser();
        if (empty($hasBackendUser))
            return $this->redirectToRoute('contao_backend');

        $repo = new Repository($this->database);
        $strQuery = "SELECT * FROM tl_consentDirectory";
		$consents = $repo->findAllAssoc($strQuery,[], []);
		
		$datum = new Datetime("now");
		
		//CSV Name
		$csvName = 'Einwilligungs-Log';
		$csvName .= '-'.$datum->format('Y-m-d');
		$csvName .= ".csv";
		
		$data = [];
		foreach ($consents as $key => $consent) {
			$data[] = [
                'ID' => $consent['id'],
                'PID' => $consent['pid'],
                'Datum' => $consent['date'],
                'Domain' => $consent['domain'],
                'URL' => $consent['url'],
                'IP' => $consent['ip'],
                'Cookie Namen' => $consent['cookieToolsName'],
                'Technische Cookie Namen' => $consent['cookieToolsTechnicalName'],
			];
		}
		$serializer = new CsvEncoder();
		$serielleData = $serializer->encode($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';',]);
		if (!empty($serielleData)) {
			$response = new Response($serielleData);
			$response->headers->set('Content-Type', 'text/csv');
			$response->headers->set('Content-Disposition', 'attachment; filename="' . $csvName . '"');
			$response->headers->set('Pragma', "no-cache");
			$response->headers->set('Expires', "0");
			$response->headers->set('Content-Transfer-Encoding', "binary");
			$response->headers->set('Content-Length', strlen($serielleData));
			return $response;
		} else {
			return $this->redirectToRoute('contao_backend');
		}
	}
	
	public function renderLink()
	{
		return '<a href="/consent/download" target="_blank" title="'.$GLOBALS['TL_LANG']['tl_consentDirectory']['download'][0].'">'.$GLOBALS['TL_LANG']['tl_consentDirectory']['download'][0].'</a>';
		
	}
}