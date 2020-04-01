<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use Contao\FrontendIndex;
use Contao\PageModel;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Netzhirsch\CookieOptInBundle\Classes\CookieData;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Contao\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;


class CookieController extends AbstractController
{

    /**
     * @Route("/cookie/allowed", name="cookie_allowed")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
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

        if ($newConsent) {

            $cookieData = new CookieData();
            $cookieData->setId($this->changeConsent($cookiesToSet,$data['modID'],$cookieDatabase));
            $cookieData->setVersion(intval($cookieDatabase['cookieVersion']));
            $cookieData->setOtherCookieIds($data['cookieIds']);
            $this->setNetzhirschCookie(
                $cookieData,
                $data['modID'],
                $cookieDatabase['cookieExpiredTime']
            );
        }
        /**
         * no JS
         */
        $noScript = $request->get('noScript');
        if ($noScript) {
            $this->initializeContaoFramework();
            $currentPageId = $request->get('currentPageId');
            $pageModel = PageModel::findById($currentPageId);
            /** @noinspection PhpParamsInspection */
            return $this->redirect('/'.$pageModel->getFrontendUrl());
        }

		$response = [
			'tools' => $cookiesToSet['cookieTools'],
			'otherScripts' => $cookiesToSet['otherScripts'],
		];
		return new JsonResponse($response);
	}

    /**
     * @param CookieData $cookieData
     * @param $modID
     * @param $cookieExpiredTime
     * @throws DBALException
     */
	private function setNetzhirschCookie($cookieData,$modID,$cookieExpiredTime){
		
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
		$cookieExpiredTime = strtotime('+'.$cookieExpiredTime.' day',$expiredDate->getTimestamp());

		$netzhirschOptInCookie = [
            'cookieId' => $cookieData->getId(),
			'cookieIds' => $cookieData->getOtherCookieIds(),
			'cookieVersion' => $cookieData->getVersion(),
		];
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
     * @param $cookieDatabase
     * @return string
     * @throws DBALException
     */
	private function changeConsent($cookieData,$modID,$cookieDatabase)
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
            'consentURL' => '',
            'cookieId' => null,
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
            $cookieData = self::getUserCookie($requestStack);
            $userInfo['cookieId'] = $cookieData->getId();
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

        $sql = "INSERT INTO tl_consentDirectory (ip,cookieToolsName,cookieToolsTechnicalName,date,domain,url,pid) VALUES(?,?,?,?,?,?,?)";

		$stmt = $conn->prepare($sql);

		$stmt->bindValue(1, $userInfo['ip']);
		$cookieNames = [];
		$cookieTechnicalName = [];
		foreach ($cookieData->getOtherCookieIds() as $cookieTool) {
            foreach ($cookieDatabase['cookieTools'] as $cookieDataFromDb) {
                if ($cookieDataFromDb['id'] == $cookieTool) {
                    $cookieNames[] = $cookieDataFromDb['cookieToolsName'];
                    $cookieTechnicalName[] = $cookieDataFromDb['cookieToolsTechnicalName'];
                }
            }
		}
        $stmt->bindValue(2, implode(', ', $cookieNames));
        $stmt->bindValue(3, implode(', ', $cookieTechnicalName));
        $stmt->bindValue(4, date('Y-m-d H:i'));
        $stmt->bindValue(5, $_SERVER['HTTP_HOST']);
        $stmt->bindValue(6, $userInfo['consentURL']);
        $stmt->bindValue(6, $userInfo['consentURL']);
        $stmt->bindValue(7, ($userInfo['cookieId']) == '' ? null : $userInfo['cookieId']);

        $stmt->execute();

        return $conn->lastInsertId();

	}

    /**
     * @param RequestStack $requestStack
     * @return CookieData
     */
	public static function getUserCookie($requestStack)
    {
        $cookieData = new CookieData();
        if (!empty($requestStack)) {
            $currentRequest = $requestStack->getCurrentRequest();
            if (!empty($currentRequest)) {
                $cookieSet = $currentRequest->cookies;
                if (!empty($cookieSet)) {
                    $cookieSet = $cookieSet->get('_netzhirsch_cookie_opt_in');
                    if (!empty($cookieSet)) {
                        /** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this*/
                        $cookieId = json_decode($cookieSet);
                        $cookieData->setId(intval($cookieId->cookieId));
                        $cookieData->setVersion($cookieId->cookieVersion);
                        $cookieData->setOtherCookieIds($cookieId->cookieIds);
                    }
                }
            }
        }
        return $cookieData;
    }
}