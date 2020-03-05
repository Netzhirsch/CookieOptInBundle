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
	 * @Route("/consent/download", name="consent_download")
	 * @throws DBALException
	 * @throws Exception
	 */
	public function indexAction()
	{
		/* @var \Symfony\Bundle\FrameworkBundle\Controller\string $entityManerString */
		$entityManerString = 'database_connection';
		/* @var Connection $conn */
		$conn = $this->get($entityManerString);
		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM tl_consentDirectory";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$consents = $stmt->fetchAll();
		
		$datum = new Datetime("now");
		
		//CSV Name
		$csvName = 'Einwilligungs-Log';
		$csvName .= '-'.$datum->format('Y-m-d');
		$csvName .= ".csv";
		
		$zeilenNummer = 1;
		$data = [];
		foreach ($consents as $key => $consent) {
			$data[] = [
					'Nr.'                  => $zeilenNummer++,
					'Datum'                => $consent['date'],
					'Domain'                => $consent['domain'],
					'IP'                => $consent['ip'],
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
			/* @var \Symfony\Bundle\FrameworkBundle\Controller\string $controllerString */
			$controllerString = 'contao_backend';
			return $this->redirectToRoute($controllerString);
		}
	}
	
	public function renderLink()
	{
		return '<a href="/consent/download" target="_blank" title="'.$GLOBALS['TL_LANG']['tl_consentDirectory']['download'][0].'">'.$GLOBALS['TL_LANG']['tl_consentDirectory']['download'][0].'</a>';
		
	}
}