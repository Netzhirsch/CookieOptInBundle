<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
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
     */
	public function allowedAction(Request $request)
	{
		$data = $request->get('data');
        $newConsent = $data['newConsent'];

		$cookieDatabase = $this->getModulData($data['modID']);
		$cookiesToDelete = [];
		$cookiesToSet = [];
		foreach ($cookieDatabase['cookieTools'] as $cookieTool) {
			if (!in_array($cookieTool['id'],$data['cookieIds'])) {
				$cookiesToDelete[] = $cookieTool;
			} else {
				$cookiesToSet['cookieTools'][] = $cookieTool;
			}
		}
        if (!isset($cookiesToSet['otherScripts'])) {
            $cookiesToSet['otherScripts'] = [];
        }
		foreach ($cookieDatabase['otherScripts'] as $otherScripts) {
			if (!in_array($otherScripts['id'],$data['cookieIds'])) {
				$cookiesToDelete[] = $otherScripts;
			}else {
				$cookiesToSet['otherScripts'][] = $otherScripts;
			}
		}

		if (!empty($cookiesToDelete))
			PageLayoutListener::deleteCookie($cookiesToDelete);

		$this->setNetzhirschCookie(
			true,
			$data['cookieIds'],
			$data['modID'],
			$cookieDatabase['cookieVersion'],
			$cookieDatabase['cookieExpiredTime']
		);

        if ($newConsent)
		    $this->changeConsent($cookiesToSet,$data['modID']);

		$response = [
			'tools' => $cookiesToSet['cookieTools'],
			'otherScripts' => $cookiesToSet['otherScripts'],
		];
		
		return new JsonResponse($response);
	}
	
	/**
	 * @param $allowed
	 * @param $cookieIds
	 * @param $modID
	 * @param $cookieVersion
	 * @param $cookieExpiredTime
	 * @throws DBALException
	 */
	private function setNetzhirschCookie($allowed,$cookieIds,$modID,$cookieVersion,$cookieExpiredTime){
		
		$netzhirschOptInCookie = [
			'allowed' => $allowed,
			'cookieVersion' => $cookieVersion,
			'cookieIds' => $cookieIds,
		];
		/* @var Connection $conn */
		/** @noinspection PhpParamsInspection */
        /** @noinspection MissingService */
        $conn = $this->get('database_connection');
		$sql = "SELECT cookieToolsTechnicalName FROM tl_fieldpalette WHERE cookieToolsTechnicalName = ? AND pid = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, '_netzhirsch_cookie_opt_in');
		$stmt->bindValue(2, $modID);
		$stmt->execute();
		$cookieToolsTechnicalName = $stmt->fetch();
		$cookieToolsTechnicalName = $cookieToolsTechnicalName['cookieToolsTechnicalName'];
		
		$expiredDate = new DateTime();
		$cookieExpiredTime = strtotime('+'.$cookieExpiredTime.' month',$expiredDate->getTimestamp());
		
		/** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this*/
		setcookie($cookieToolsTechnicalName, json_encode($netzhirschOptInCookie),$cookieExpiredTime,'/',$_SERVER['HTTP_HOST']);
	}
	
	/**
	 * @param $modID
	 * @return mixed
	 * @throws DBALException
	 */
	private function getModulData($modID){
		
		$response = [];
		
		/** @noinspection PhpParamsInspection */
        /** @noinspection MissingService */
		$conn = $this->get('database_connection');
		/* @var Connection $conn */
		$sql = "SELECT cookieVersion,cookieExpiredTime FROM tl_module WHERE type = ? AND id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, 'cookieOptInBar');
		$stmt->bindValue(2, $modID);
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
			'cookieToolsTrackingID',
			'cookieToolsTrackingServerUrl',
			'cookieToolsSelect',
			'cookieToolsUse',
			'cookieToolGroup',
			'cookieToolExpiredTime',
		];
		$sql = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$sql .= ' WHERE pid = ? AND pfield = ?';
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $modID);
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
			'cookieToolExpiredTime',
			'cookieToolGroup',
		];
		$sql = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$sql .= ' WHERE pid = ? AND pfield = ?';
		
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $modID);
		$stmt->bindValue(2, 'otherScripts');
		
		$stmt->execute();
		$otherScripts = $stmt->fetchAll();
		
		$response['cookieTools'] = $tools;
		$response['otherScripts'] = $otherScripts;
		
		return $response;
	}

    /**
     * @param $cookieData
     * @param $modID
     * @throws DBALException
     */
	private function changeConsent($cookieData,$modID)
	{
		/** @noinspection PhpParamsInspection */
		$requestStack = $this->get('request_stack');
		/* @var Connection $conn */
		/** @noinspection PhpParamsInspection */
        /** @noinspection MissingService */
        $conn = $this->get('database_connection');

        $sql = "SELECT ipFormatSave FROM tl_module WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modID);
        $stmt->execute();
        $ipFormatSave = $stmt->fetchColumn();

        //Besucher Infos
        $currentRequest = $requestStack->getCurrentRequest();
        $userInfo = [
            'ip' => '',
            'consentURL' => ''
        ];
        if (!empty($currentRequest)) {

            $userInfo['ip'] = $currentRequest->getClientIp();
            if ($userInfo['ip'] == '::1')
                $userInfo['ip'] = '127.0.0.1';

            $headers = $currentRequest->headers;
            if (!empty($headers)) {
                $referer = $headers->get('referer');
                if (!empty($referer))
                    $userInfo['consentURL'] = $referer;
            }
        }

        if (!empty($ipFormatSave) && $ipFormatSave != 'uncut') {
            $userInfo['ip'] = explode('.',$userInfo['ip']);
            end($userInfo['ip']);
            $lastIndex = key($userInfo['ip']);
            $userInfo['ip'][$lastIndex] = '*';
            if ($ipFormatSave == 'anon')
                $userInfo['ip'][--$lastIndex] = '*';

            $userInfo['ip'] = implode('.',$userInfo['ip']);
        }
        $sql = "SELECT ip FROM tl_consentDirectory WHERE ip = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, $userInfo['ip']);
		$stmt->execute();
		$ipInDB = $stmt->fetchColumn();

		if (empty($ipInDB)){
            $sql = "INSERT INTO tl_consentDirectory (ip,cookieToolsName,cookieToolsTechnicalName,date,domain,url) VALUES(?,?,?,?,?,?)";
		} else {
            $sql = "UPDATE tl_consentDirectory SET ip = ? ,cookieToolsName = ?, cookieToolsTechnicalName = ? ,date = ?, domain = ?, url = ? WHERE ip = ?";
		}

		$stmt = $conn->prepare($sql);

		$stmt->bindValue(1, $userInfo['ip']);
		$cookieNames = [];
		$cookieTechnicalName = [];
		$cookieTools = $cookieData['cookieTools'];
		foreach ($cookieTools as $cookieTool) {
			$cookieNames[] = $cookieTool['cookieToolsName'];
			$cookieTechnicalName[] = $cookieTool['cookieToolsTechnicalName'];
		}
        if (isset($cookieData['otherScripts'])) {
            $otherScripts = $cookieData['otherScripts'];
            foreach ($otherScripts as $otherScript) {
                $cookieNames[] = $otherScript['cookieToolsName'];
                $cookieTechnicalName[] = $otherScript['cookieToolsTechnicalName'];
            }
        }
        $stmt->bindValue(2, implode(', ', $cookieNames));
        $stmt->bindValue(3, implode(', ', $cookieTechnicalName));
        $stmt->bindValue(4, date('Y-m-d H:i'));
        $stmt->bindValue(5, $_SERVER['HTTP_HOST']);
        $stmt->bindValue(6, $userInfo['consentURL']);

        if (!empty($ipInDB))
            $stmt->bindValue(7, $userInfo['ip']);

        $stmt->execute();

	}
}