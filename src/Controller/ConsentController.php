<?php


namespace Netzhirsch\CookieOptInBundle\Controller;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

class ConsentController extends AbstractController
{
	/**
	 * @Route("/consent/download", name="consent_download",  defaults={"_scope" = "backend"})
	 * @throws DBALException
	 * @throws Exception
	 */
	public function indexAction()
	{
        $hasBackendUser = $this->getUser();
        if (empty($hasBackendUser))
            return $this->redirectToRoute('contao_backend');
		/* @var Connection $conn */
        $conn = $this->get('database_connection');
        $sql = "SELECT * FROM tl_consentDirectory";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$consents = $stmt->fetchAll();
		
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