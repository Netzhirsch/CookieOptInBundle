<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class CookieController extends AbstractController
{
	
	/**
	 * @Route("/cookie/allowed", name="cookie_allowed")
	 * @param Request $request
	 * @return JsonResponse
	 * @throws DBALException
	 * @throws Exception
	 */
	public function allowedAction(Request $request)
	{
		$selected = $request->get('selected');
		
		$cookieDatabase = $this->getModulData($selected['modId']);
		$cookiesToDelete = [];
		$cookiesToSet = [];
		foreach ($cookieDatabase['cookieTools'] as $cookieTool) {
			if (!in_array($cookieTool['id'],$selected['cookieIds'])) {
				$cookiesToDelete[] = $cookieTool;
			} else {
				$cookiesToSet['cookieTools'][] = $cookieTool;
			}
		}
		foreach ($cookieDatabase['otherScripts'] as $otherScripts) {
			if (!in_array($otherScripts['id'],$selected['cookieIds'])) {
				$cookiesToDelete[] = $otherScripts;
			}else {
				$cookiesToSet['otherScripts'][] = $otherScripts;
			}
		}
		
		if (!empty($cookiesToDelete))
			PageLayoutListener::deleteCookie($cookiesToDelete);
		
		$this->setNetzhirschCookie(
			true,
			$selected['cookieIds'],
			$selected['modId'],
			$cookieDatabase['cookieVersion'],
			$cookieDatabase['cookieExpiredTime']
		);
		
		$this->changeConsent($cookiesToSet);
		
		$response = [
			'tools' => $cookiesToSet['cookieTools'],
			'otherScripts' => $cookiesToSet['otherScripts'],
		];
		
		return new JsonResponse($response);
	}
	
	/**
	 * @param $allowed
	 * @param $cookieIds
	 * @param $modId
	 * @param $cookieVersion
	 * @param $cookieExpiredTime
	 * @throws DBALException
	 */
	private function setNetzhirschCookie($allowed,$cookieIds,$modId,$cookieVersion,$cookieExpiredTime){
		
		$netzhirschOptInCookie = [
			'allowed' => $allowed,
			'cookieVersion' => $cookieVersion,
			'cookieIds' => $cookieIds,
		];
		/* @var \Symfony\Bundle\FrameworkBundle\Controller\string $entityManerString */
		$entityManerString = 'database_connection';
		/* @var Connection $conn */
		$conn = $this->get($entityManerString);
		/** @noinspection SqlResolve */
		$sql = "SELECT cookieToolsTechnicalName FROM tl_fieldpalette WHERE cookieToolsTechnicalName = ? AND pid = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, '_netzhirsch_cookie_opt_in');
		$stmt->bindValue(2, $modId);
		$stmt->execute();
		$cookieToolsTechnicalName = $stmt->fetch();
		$cookieToolsTechnicalName = $cookieToolsTechnicalName['cookieToolsTechnicalName'];
		
		$expiredDate = new DateTime();
		$cookieExpiredTime = strtotime('+'.$cookieExpiredTime.' month',$expiredDate->getTimestamp());
		
		/** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this*/
		setcookie($cookieToolsTechnicalName, json_encode($netzhirschOptInCookie),$cookieExpiredTime,'/',$_SERVER['HTTP_HOST']);
	}
	
	/**
	 * @param $modId
	 * @param null $cookieIds
	 * @return mixed
	 * @throws DBALException
	 */
	private function getModulData($modId){
		
		$response = [];
		
		/* @var \Symfony\Bundle\FrameworkBundle\Controller\string $entityManerString */
		$entityManerString = 'database_connection';
		$conn = $this->get($entityManerString);
		/* @var Connection $conn */
		/** @noinspection SqlResolve */
		$sql = "SELECT cookieVersion,cookieExpiredTime FROM tl_module WHERE type = ? AND id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, 'cookieOptInBar');
		$stmt->bindValue(2, $modId);
		$stmt->execute();
		$data = $stmt->fetch();
		
		$response['cookieVersion'] = $data['cookieVersion'];
		$response['cookieExpiredTime'] = $data['cookieExpiredTime'];
		
		$select = [
			'id',
			'cookieToolsName',
			'cookieToolsTechnicalName',
			'cookieToolsPrivacyPolicyUrl',
			'cookieToolsProvider',
			'cookieToolsTrackingId',
			'cookieToolsTrackingServerUrl',
			'cookieToolsSelect',
			'cookieToolsUse',
			'cookieToolGroup',
		];
		$sql = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$sql .= ' WHERE pid = ? AND pfield = ?';
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $modId);
		$stmt->bindValue(2, 'cookieTools');
		
		$stmt->execute();
		$tools = $stmt->fetchAll();
		
		$select = [
			'id',
			'cookieToolsName',
			'cookieToolsTechnicalName',
			'cookieToolsPrivacyPolicyUrl',
			'cookieToolsProvider',
			'cookieToolsUse',
			'cookieToolsCode',
			'cookieToolsCode',
			'cookieToolGroup',
		];
		$sql = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$sql .= ' WHERE pid = ? AND pfield = ?';
		
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $modId);
		$stmt->bindValue(2, 'otherScripts');
		
		$stmt->execute();
		$otherScripts = $stmt->fetchAll();
		
		$response['cookieTools'] = $tools;
		$response['otherScripts'] = $otherScripts;
		
		return $response;
	}
	
	/**
	 * @param $cookieData
	 * @throws DBALException
	 */
	private function changeConsent($cookieData)
	{
		/* @var \Symfony\Bundle\FrameworkBundle\Controller\string $entityManerString */
		$entityManerString = 'request_stack';
		$requestStack = $this->get($entityManerString);
		$entityManerString = 'database_connection';
		/* @var Connection $conn */
		$conn = $this->get($entityManerString);
		$ipCurrentUser = $requestStack->getCurrentRequest()->getClientIp();
		/** @noinspection SqlResolve */
		$sql = "SELECT ip FROM tl_consentDirectory WHERE ip = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, $ipCurrentUser);
		$stmt->execute();
		$ipInDB = $stmt->fetchColumn();
		
		if (empty($ipInDB)){
			/** @noinspection SqlResolve */
			$sql = "INSERT INTO tl_consentDirectory (ip,cookieToolsName,cookieToolsTechnicalName,date,domain) VALUES(?,?,?,?,?)";
		} else {
			/** @noinspection SqlResolve */
			$sql = "UPDATE tl_consentDirectory SET ip = ? ,cookieToolsName = ?, cookieToolsTechnicalName = ? ,date = ?, domain = ? WHERE ip = ?";
		}
		
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $ipCurrentUser);
		$cookieNames = [];
		$cookieTechnicalName = [];
		$cookieTools = $cookieData['cookieTools'];
		foreach ($cookieTools as $cookieTool) {
			$cookieNames[] = $cookieTool['cookieToolsName'];
			$cookieTechnicalName[] = $cookieTool['cookieToolsTechnicalName'];
		}
		$otherScripts = $cookieData['otherScripts'];
		foreach ($otherScripts as $otherScript) {
			$cookieNames[] = $otherScript['cookieToolsName'];
			$cookieTechnicalName[] = $otherScript['cookieToolsTechnicalName'];
		}
		$stmt->bindValue(2, implode(', ', $cookieNames));
		$stmt->bindValue(3, implode(', ', $cookieTechnicalName));
		$stmt->bindValue(4, date('Y-m-d H:i'));
		$stmt->bindValue(5, $_SERVER['HTTP_HOST']);
		
		if (!empty($ipInDB))
			$stmt->bindValue(6, $ipCurrentUser);
		
		$stmt->execute();
		
	}
}